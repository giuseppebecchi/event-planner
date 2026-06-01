<?php

namespace App\Support;

use App\Models\ProjectLayoutElement;
use App\Models\ProjectSeatingPlan;
use App\Models\ProjectTable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SeatingPlanMapRenderer
{
    public const PDF_SCALE = 0.5;

    public function render(ProjectSeatingPlan $plan, array|Collection $people = [], bool $showAssignments = false): string
    {
        $people = collect($people)->keyBy('key');
        $plan->loadMissing(['tables', 'layoutElements']);

        $svg = [
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1400 900" width="1400" height="900">',
            '<rect x="0" y="0" width="1400" height="900" fill="#faf7f2"/>',
            '<style><![CDATA[
                .grid { stroke: rgba(121,112,102,.13); stroke-width: 1; }
                .table { fill: #fffdf9; stroke: #7a8f7b; stroke-width: 2.5; }
                .table-label { fill: #2d2a26; font-size: 13px; font-weight: 900; text-anchor: middle; dominant-baseline: middle; font-family: DejaVu Sans, Arial, sans-serif; }
                .layout-element { stroke: #a88f62; stroke-width: 2; stroke-dasharray: 6 4; }
                .layout-label { fill: #4b433b; font-size: 15px; font-weight: 900; text-anchor: middle; dominant-baseline: middle; font-family: DejaVu Sans, Arial, sans-serif; }
                .chair-seat { fill: #fffaf2; stroke: #9d8451; stroke-width: 1.4; }
                .chair-back { fill: #d8c298; stroke: #9d8451; stroke-width: 1.4; }
                .chair.occupied .chair-seat { fill: #dfeedd; stroke: #5f8f62; }
                .chair.occupied .chair-back { fill: #a9cfa9; stroke: #5f8f62; }
                .seat-number { fill: #4f463d; font-size: 9px; font-weight: 900; text-anchor: middle; dominant-baseline: middle; font-family: DejaVu Sans, Arial, sans-serif; }
                .tag-bg { fill: #fffaf2; stroke: #c9a96a; stroke-width: 1.3; }
                .tag-text { fill: #2d2a26; font-size: 10px; font-weight: 900; text-anchor: middle; dominant-baseline: middle; font-family: DejaVu Sans, Arial, sans-serif; }
            ]]></style>',
        ];

        if ($background = $this->backgroundDataUri($plan)) {
            $svg[] = '<image href="' . $background . '" x="0" y="0" width="1400" height="900" preserveAspectRatio="xMidYMid slice" opacity=".74"/>';
        }

        for ($x = 0; $x <= 1400; $x += 50) {
            $svg[] = '<line class="grid" x1="' . $x . '" y1="0" x2="' . $x . '" y2="900"/>';
        }

        for ($y = 0; $y <= 900; $y += 50) {
            $svg[] = '<line class="grid" x1="0" y1="' . $y . '" x2="1400" y2="' . $y . '"/>';
        }

        foreach ($plan->layoutElements as $element) {
            $svg[] = $this->renderLayoutElement($element);
        }

        foreach ($plan->tables as $table) {
            $svg[] = $this->renderTable($table, $people, $showAssignments);
        }

        $svg[] = '</svg>';

        return implode('', $svg);
    }

    public function storePreview(ProjectSeatingPlan $plan): string
    {
        $svg = $this->render($plan);

        if ($plan->preview_image_path) {
            Storage::disk('public')->delete($plan->preview_image_path);
        }

        $path = 'projects/seating-plans/previews/' . Str::uuid() . '.svg';
        Storage::disk('public')->put($path, $svg);
        $plan->forceFill(['preview_image_path' => $path])->save();

        return $path;
    }

    public function pdfMapData(ProjectSeatingPlan $plan, array|Collection $people = []): array
    {
        $people = collect($people)->keyBy('key');
        $plan->loadMissing(['tables', 'layoutElements']);

        return [
            'width' => 1400 * self::PDF_SCALE,
            'height' => 900 * self::PDF_SCALE,
            'background' => $this->backgroundDataUri($plan),
            'tables' => $plan->tables->map(function (ProjectTable $table) use ($people): array {
                $width = (float) $table->primary_dimension;
                $height = $table->secondary_dimension !== null ? (float) $table->secondary_dimension : $width;
                $rotation = (float) $table->rotation;
                $assignments = $this->normalizedAssignments($table);

                return [
                    'name' => $table->name,
                    'type' => $table->table_type,
                    'curve_count' => (int) ($table->curve_count ?? 0),
                    'curve_type' => $table->curve_type ?: ($table->table_type === 'chair_row' ? 'none' : 'medium'),
                    'x' => ((float) $table->center_x) * self::PDF_SCALE,
                    'y' => ((float) $table->center_y) * self::PDF_SCALE,
                    'width' => $width * self::PDF_SCALE,
                    'height' => $height * self::PDF_SCALE,
                    'rotation' => $rotation,
                    'seats' => collect($this->seats($table))->map(function (array $seat) use ($table, $rotation, $assignments, $people): array {
                        $global = $this->globalPoint((float) $table->center_x, (float) $table->center_y, (float) $seat['x'], (float) $seat['y'], $rotation);
                        $tag = $this->tagPosition($seat);
                        $globalTag = $this->globalPoint((float) $table->center_x, (float) $table->center_y, (float) $tag['x'], (float) $tag['y'], $rotation);
                        $guest = $people->get($assignments[$seat['number']] ?? null);

                        return [
                            'number' => $seat['number'],
                            'x' => $global['x'] * self::PDF_SCALE,
                            'y' => $global['y'] * self::PDF_SCALE,
                            'tag_x' => $globalTag['x'] * self::PDF_SCALE,
                            'tag_y' => $globalTag['y'] * self::PDF_SCALE,
                            'guest' => $guest,
                            'label' => $guest['label'] ?? null,
                        ];
                    })->all(),
                ];
            })->all(),
        ];
    }

    protected function renderLayoutElement(ProjectLayoutElement $element): string
    {
        $width = max(20, (float) $element->width);
        $height = max(20, (float) $element->height);
        $fill = $element->background_color ?: 'transparent';
        $label = e((string) $element->label);
        $content = [];

        $content[] = '<g transform="translate(' . (float) $element->center_x . ',' . (float) $element->center_y . ') rotate(' . (float) $element->rotation . ')">';

        if ($element->element_type === 'space') {
            if ($element->shape === 'circle') {
                $content[] = '<ellipse class="layout-element" cx="0" cy="0" rx="' . ($width / 2) . '" ry="' . ($height / 2) . '" fill="' . e($fill) . '"/>';
            } else {
                $content[] = '<rect class="layout-element" x="' . (-$width / 2) . '" y="' . (-$height / 2) . '" width="' . $width . '" height="' . $height . '" rx="8" fill="' . e($fill) . '"/>';
            }
        }

        if ($label !== '') {
            $content[] = '<text class="layout-label" x="0" y="0">' . $label . '</text>';
        }

        $content[] = '</g>';

        return implode('', $content);
    }

    protected function renderTable(ProjectTable $table, Collection $people, bool $showAssignments): string
    {
        $width = (float) $table->primary_dimension;
        $height = $table->secondary_dimension !== null ? (float) $table->secondary_dimension : $width;
        $isRound = in_array($table->table_type, ['round', 'oval'], true);
        $isChairRow = $table->table_type === 'chair_row';
        $isLongTable = $table->table_type === 'long_table';
        $assignments = $this->normalizedAssignments($table);
        $content = [];

        $content[] = '<g transform="translate(' . (float) $table->center_x . ',' . (float) $table->center_y . ') rotate(' . (float) $table->rotation . ')">';

        if (! $isChairRow) {
            if ($isRound) {
                $content[] = '<ellipse class="table" cx="0" cy="0" rx="' . ($width / 2) . '" ry="' . ($height / 2) . '"/>';
            } elseif ($isLongTable) {
                $content[] = '<path class="table" d="' . $this->longTablePath($table) . '"/>';
            } else {
                $content[] = '<rect class="table" x="' . (-$width / 2) . '" y="' . (-$height / 2) . '" width="' . $width . '" height="' . $height . '" rx="7"/>';
            }
        }

        foreach ($this->seats($table) as $seat) {
            $guest = $people->get($assignments[$seat['number']] ?? null);
            $occupiedClass = $guest ? ' occupied' : '';

            $content[] = '<g class="chair' . $occupiedClass . '" transform="translate(' . $seat['x'] . ',' . $seat['y'] . ') rotate(' . $seat['rotation'] . ')">';
            $content[] = '<rect class="chair-seat" x="-8" y="-6" width="16" height="14" rx="4"/>';
            $content[] = '<rect class="chair-back" x="-10" y="-14" width="20" height="7" rx="3"/>';
            $content[] = '<line class="chair-seat" x1="-6" y1="9" x2="-6" y2="13"/>';
            $content[] = '<line class="chair-seat" x1="6" y1="9" x2="6" y2="13"/>';
            $content[] = '<text class="seat-number" x="0" y="1">' . $seat['number'] . '</text>';
            $content[] = '</g>';

            if ($showAssignments && $guest) {
                $tag = $this->tagPosition($seat);
                $name = e((string) ($guest['label'] ?? $guest['short'] ?? 'Guest'));
                $tagWidth = max(72, min(150, mb_strlen(strip_tags($name)) * 6 + 18));

                $content[] = '<g transform="translate(' . $tag['x'] . ',' . $tag['y'] . ')">';
                $content[] = '<rect class="tag-bg" x="' . (-$tagWidth / 2) . '" y="-10" width="' . $tagWidth . '" height="20" rx="8"/>';
                $content[] = '<text class="tag-text" x="0" y="1">' . $name . '</text>';
                $content[] = '</g>';
            }
        }

        $content[] = '<text class="table-label" x="0" y="' . ($isChairRow ? 34 : 0) . '">' . e($table->name) . '</text>';
        $content[] = '</g>';

        return implode('', $content);
    }

    protected function seats(ProjectTable $table): array
    {
        $seats = [];
        $width = (float) $table->primary_dimension;
        $height = $table->secondary_dimension !== null ? (float) $table->secondary_dimension : $width;
        $seatGap = 18;
        $chairInset = 7;

        if ($table->table_type === 'chair_row') {
            return $this->chairRowSeats($table);
        }

        if (in_array($table->table_type, ['round', 'oval'], true)) {
            $count = (int) ($table->seats_total ?? 0);
            $radiusX = ($width / 2) + $seatGap - $chairInset;
            $radiusY = ($height / 2) + $seatGap - $chairInset;

            for ($index = 0; $index < $count; $index++) {
                $angle = (M_PI * 2 * $index / max($count, 1)) - (M_PI / 2);
                $seats[] = [
                    'number' => $index + 1,
                    'x' => cos($angle) * $radiusX,
                    'y' => sin($angle) * $radiusY,
                    'rotation' => ($angle * 180 / M_PI) + 90,
                ];
            }

            return $seats;
        }

        if ($table->table_type === 'long_table') {
            return $this->longTableSeats($table, $seatGap, $chairInset);
        }

        $number = 1;
        $bySide = $table->seats_by_side_json ?? ['top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0];

        foreach (['top', 'right', 'bottom', 'left'] as $side) {
            $count = (int) ($bySide[$side] ?? 0);

            for ($index = 0; $index < $count; $index++) {
                $ratio = ($index + 1) / ($count + 1);
                $seats[] = match ($side) {
                    'top' => ['number' => $number++, 'x' => -$width / 2 + $width * $ratio, 'y' => -$height / 2 - $seatGap + $chairInset, 'rotation' => 0],
                    'right' => ['number' => $number++, 'x' => $width / 2 + $seatGap - $chairInset, 'y' => -$height / 2 + $height * $ratio, 'rotation' => 90],
                    'bottom' => ['number' => $number++, 'x' => -$width / 2 + $width * $ratio, 'y' => $height / 2 + $seatGap - $chairInset, 'rotation' => 180],
                    'left' => ['number' => $number++, 'x' => -$width / 2 - $seatGap + $chairInset, 'y' => -$height / 2 + $height * $ratio, 'rotation' => 270],
                };
            }
        }

        return $seats;
    }

    protected function longTablePath(ProjectTable $table, float $inflate = 0): string
    {
        $length = max(100, (float) $table->primary_dimension) + ($inflate * 2);
        $width = ProjectTable::LONG_TABLE_WIDTH + ($inflate * 2);
        $halfWidth = $width / 2;
        $curves = max(0, min(4, (int) ($table->curve_count ?? 0)));
        $steps = max(18, max(1, $curves) * 14);
        $top = [];
        $bottom = [];

        for ($index = 0; $index <= $steps; $index++) {
            $progress = $index / $steps;
            $center = $this->longTableCurvePoint($table, $progress, $length, $inflate);

            $top[] = [
                $center['x'] - ($center['normal_x'] * $halfWidth),
                $center['y'] - ($center['normal_y'] * $halfWidth),
            ];
            $bottom[] = [
                $center['x'] + ($center['normal_x'] * $halfWidth),
                $center['y'] + ($center['normal_y'] * $halfWidth),
            ];
        }

        $points = array_merge($top, array_reverse($bottom));

        return collect($points)
            ->map(fn (array $point, int $index): string => ($index === 0 ? 'M' : 'L') . ' ' . round($point[0], 1) . ' ' . round($point[1], 1))
            ->implode(' ') . ' Z';
    }

    protected function chairRowSeats(ProjectTable $table): array
    {
        $seats = [];
        $count = (int) ($table->seats_total ?? 0);
        $spacing = ProjectTable::CHAIR_ROW_SPACING;
        $startX = - (($count - 1) * $spacing) / 2;
        $curveType = $table->curve_type ?: 'none';

        if ($curveType === 'none' || $count <= 1) {
            for ($index = 0; $index < $count; $index++) {
                $seats[] = [
                    'number' => $index + 1,
                    'x' => $startX + ($index * $spacing),
                    'y' => 0,
                    'rotation' => 180,
                ];
            }

            return $seats;
        }

        $chord = max($spacing, ($count - 1) * $spacing);
        $sagitta = ($curveType === 'high' ? 30 : 12);
        $radius = (($chord * $chord) / (8 * $sagitta)) + ($sagitta / 2);
        $centerY = $sagitta - $radius;
        $halfAngle = asin(min(0.98, ($chord / 2) / $radius));

        for ($index = 0; $index < $count; $index++) {
            $ratio = $count === 1 ? 0.5 : $index / ($count - 1);
            $angle = -$halfAngle + ($halfAngle * 2 * $ratio);
            $x = sin($angle) * $radius;
            $y = cos($angle) * $radius - $radius + $sagitta;
            $rotation = (atan2($centerY - $y, -$x) * 180 / M_PI) - 90;

            $seats[] = [
                'number' => $index + 1,
                'x' => $x,
                'y' => $y,
                'rotation' => $rotation,
            ];
        }

        return $seats;
    }

    protected function curveAmplitude(ProjectTable $table, float $inflate = 0): float
    {
        if ((int) ($table->curve_count ?? 0) === 0) {
            return 0;
        }

        $width = ProjectTable::LONG_TABLE_WIDTH + ($inflate * 2);
        $factor = match ($table->curve_type) {
            'subtle' => 0.34,
            'strong' => 1.12,
            default => 0.72,
        };

        return max(14, $width * $factor);
    }

    protected function longTableCurvePoint(ProjectTable $table, float $progress, ?float $length = null, float $inflate = 0): array
    {
        $length = $length ?? max(100, (float) $table->primary_dimension);
        $curves = max(0, min(4, (int) ($table->curve_count ?? 0)));
        $amplitude = $this->curveAmplitude($table, $inflate);
        $x = -($length / 2) + ($length * $progress);
        $angle = $progress * max(1, $curves) * M_PI;
        $y = $curves > 0 ? sin($angle) * $amplitude : 0;
        $slope = $curves > 0 ? cos($angle) * $amplitude * $curves * M_PI / $length : 0;
        $normalLength = sqrt(($slope * $slope) + 1);
        $normalX = -$slope / $normalLength;
        $normalY = 1 / $normalLength;
        $tangentLength = sqrt(1 + ($slope * $slope));

        return [
            'x' => $x,
            'y' => $y,
            'normal_x' => $normalX,
            'normal_y' => $normalY,
            'tangent_x' => 1 / $tangentLength,
            'tangent_y' => $slope / $tangentLength,
        ];
    }

    protected function longTableSeats(ProjectTable $table, float $seatGap, float $chairInset): array
    {
        $seats = [];
        $number = 1;
        $length = max(100, (float) $table->primary_dimension);
        $halfWidth = ProjectTable::LONG_TABLE_WIDTH / 2;
        $distance = $halfWidth + $seatGap - $chairInset;
        $bySide = $table->seats_by_side_json ?? ['top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0];
        $rotation = fn (float $x, float $y): float => (atan2($y, $x) * 180 / M_PI) + 90;

        foreach (['top', 'bottom'] as $side) {
            $count = (int) ($bySide[$side] ?? 0);

            for ($index = 0; $index < $count; $index++) {
                $point = $this->longTableCurvePoint($table, ($index + 1) / ($count + 1), $length);
                $direction = $side === 'top' ? -1 : 1;
                $outX = $point['normal_x'] * $direction;
                $outY = $point['normal_y'] * $direction;

                $seats[] = [
                    'number' => $number++,
                    'x' => $point['x'] + ($outX * $distance),
                    'y' => $point['y'] + ($outY * $distance),
                    'rotation' => $rotation($outX, $outY),
                ];
            }
        }

        foreach (['right', 'left'] as $side) {
            $count = (int) ($bySide[$side] ?? 0);
            $endpoint = $this->longTableCurvePoint($table, $side === 'right' ? 1 : 0, $length);
            $outX = $endpoint['tangent_x'] * ($side === 'right' ? 1 : -1);
            $outY = $endpoint['tangent_y'] * ($side === 'right' ? 1 : -1);

            for ($index = 0; $index < $count; $index++) {
                $ratio = ($index + 1) / ($count + 1);
                $offset = -$halfWidth + (ProjectTable::LONG_TABLE_WIDTH * $ratio);

                $seats[] = [
                    'number' => $number++,
                    'x' => $endpoint['x'] + ($endpoint['normal_x'] * $offset) + ($outX * ($seatGap - $chairInset)),
                    'y' => $endpoint['y'] + ($endpoint['normal_y'] * $offset) + ($outY * ($seatGap - $chairInset)),
                    'rotation' => $rotation($outX, $outY),
                ];
            }
        }

        return $seats;
    }

    protected function tagPosition(array $seat): array
    {
        $rotation = (int) ((((float) $seat['rotation'] % 360) + 360) % 360);
        $offset = in_array($rotation, [90, 270], true) ? 54 : 42;
        $radians = (((float) $seat['rotation'] - 90) * M_PI) / 180;

        return [
            'x' => $seat['x'] + cos($radians) * $offset,
            'y' => $seat['y'] + sin($radians) * $offset,
        ];
    }

    protected function normalizedAssignments(ProjectTable $table): array
    {
        $assignments = $table->guest_assignments_json ?? [];

        if (array_is_list($assignments)) {
            return collect($assignments)
                ->filter()
                ->mapWithKeys(fn ($guestKey, int $index): array => [$index + 1 => $guestKey])
                ->all();
        }

        return collect($assignments)
            ->filter()
            ->mapWithKeys(fn ($guestKey, $seat): array => [(int) $seat => $guestKey])
            ->all();
    }

    public function backgroundDataUri(ProjectSeatingPlan $plan): ?string
    {
        if (! $plan->background_image_path || ! Storage::disk('public')->exists($plan->background_image_path)) {
            return null;
        }

        $path = Storage::disk('public')->path($plan->background_image_path);
        $mime = mime_content_type($path) ?: 'image/jpeg';
        $content = file_get_contents($path);

        if ($content === false) {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($content);
    }

    protected function globalPoint(float $centerX, float $centerY, float $x, float $y, float $rotation): array
    {
        $radians = ($rotation * M_PI) / 180;

        return [
            'x' => $centerX + ($x * cos($radians)) - ($y * sin($radians)),
            'y' => $centerY + ($x * sin($radians)) + ($y * cos($radians)),
        ];
    }
}
