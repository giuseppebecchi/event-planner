<div class="wm-strategic-preview">
    <style>
        .wm-strategic-preview {
            display: grid;
            gap: 1rem;
        }

        .wm-strategic-preview-content {
            padding: 1rem 1.1rem;
            border: 1px solid #e8e0d6;
            border-radius: 1rem;
            background: #fbf8f4;
            color: #3f3934;
            line-height: 1.65;
        }

        .wm-strategic-preview-content > :first-child {
            margin-top: 0;
        }

        .wm-strategic-preview-content > :last-child {
            margin-bottom: 0;
        }

        .wm-strategic-preview-images {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
        }

        .wm-strategic-preview-image {
            display: block;
            overflow: hidden;
            min-height: 12rem;
            border: 1px solid #e8e0d6;
            border-radius: 1rem;
            background: #f1ece5;
        }

        .wm-strategic-preview-image img {
            width: 100%;
            height: 100%;
            min-height: 12rem;
            max-height: 24rem;
            object-fit: cover;
        }

        .wm-strategic-preview-empty {
            margin: 0;
            padding: 1rem;
            border: 1px dashed #ddd2c5;
            border-radius: 1rem;
            color: #7b736b;
            text-align: center;
        }

        @media (max-width: 680px) {
            .wm-strategic-preview-images {
                grid-template-columns: 1fr;
            }
        }
    </style>

    @if (filled($info->content))
        <div class="wm-strategic-preview-content">
            {!! $info->content !!}
        </div>
    @endif

    @if (collect($info->image_paths ?? [])->filter()->isNotEmpty())
        <div class="wm-strategic-preview-images">
            @foreach ($info->image_paths as $imagePath)
                <a
                    class="wm-strategic-preview-image"
                    href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($imagePath) }}"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($imagePath) }}" alt="{{ $info->title }}">
                </a>
            @endforeach
        </div>
    @endif

    @if (blank($info->content) && collect($info->image_paths ?? [])->filter()->isEmpty())
        <p class="wm-strategic-preview-empty">No information has been added yet.</p>
    @endif
</div>
