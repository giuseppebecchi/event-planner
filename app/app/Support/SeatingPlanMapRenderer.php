<?php

namespace App\Support;

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
        $plan->loadMissing('tables');

        $svg = [
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1400 900" width="1400" height="900">',
            '<rect x="0" y="0" width="1400" height="900" fill="#faf7f2"/>',
            '<style><![CDATA[
                .grid { stroke: rgba(121,112,102,.13); stroke-width: 1; }
                .table { fill: #fffdf9; stroke: #7a8f7b; stroke-width: 2.5; }
                .table-label { fill: #2d2a26; font-size: 13px; font-weight: 900; text-anchor: middle; dominant-baseline: middle; font-family: DejaVu Sans, Arial, sans-serif; }
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
        $plan->loadMissing('tables');

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

    protected function renderTable(ProjectTable $table, Collection $people, bool $showAssignments): string
    {
        $width = (float) $table->primary_dimension;
        $height = $table->secondary_dimension !== null ? (float) $table->secondary_dimension : $width;
        $isRound = in_array($table->table_type, ['round', 'oval'], true);
        $isChairRow = $table->table_type === 'chair_row';
        $assignments = $this->normalizedAssignments($table);
        $content = [];

        $content[] = '<g transform="translate(' . (float) $table->center_x . ',' . (float) $table->center_y . ') rotate(' . (float) $table->rotation . ')">';

        if (! $isChairRow) {
            $content[] = $isRound
                ? '<ellipse class="table" cx="0" cy="0" rx="' . ($width / 2) . '" ry="' . ($height / 2) . '"/>'
                : '<rect class="table" x="' . (-$width / 2) . '" y="' . (-$height / 2) . '" width="' . $width . '" height="' . $height . '" rx="7"/>';
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
            $count = (int) ($table->seats_total ?? 0);
            $spacing = ProjectTable::CHAIR_ROW_SPACING;
            $startX = - (($count - 1) * $spacing) / 2;

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
