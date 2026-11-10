<article class="wm-recap-checklist-item">
    <p class="wm-recap-item-title">{{ $info->title }}</p>
    <p class="wm-recap-meta">{{ collect([$info->category?->label ?: 'Event information', $info->info->supplier?->name, $info->dateLabel()])->filter()->implode(' · ') }}</p>
    @if ($info->content)
        <div class="wm-recap-html">{!! $info->content !!}</div>
    @endif
    @if (collect($info->image_paths)->filter()->isNotEmpty())
        <div class="wm-recap-strategic-images">
            @foreach ($info->image_paths as $imagePath)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($imagePath) }}" alt="">
            @endforeach
        </div>
    @endif
</article>
