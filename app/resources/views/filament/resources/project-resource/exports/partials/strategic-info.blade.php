<div class="recap-card">
    <h3 class="recap-title">{{ $info['title'] }}</h3>
    <p class="recap-meta">{{ collect([$info['category'] ?: 'Event information', $info['supplier'] ?? null, $info['date_label'] ?? null])->filter()->implode(' · ') }}</p>
    @if ($info['content'])
        <div class="recap-html">{!! $info['content'] !!}</div>
    @endif
    @if (! empty($info['images']))
        <div class="item-images">
            @foreach ($info['images'] as $image)
                <span class="item-image"><img src="{{ $image }}" alt=""></span>
            @endforeach
        </div>
    @endif
</div>
