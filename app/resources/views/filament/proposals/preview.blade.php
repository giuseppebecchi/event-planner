<div
    x-data="{}"
    x-on:message.window="
        if ($event.origin !== window.location.origin || $event.source !== $refs.preview.contentWindow) return;
        if ($event.data.type === 'proposal-height') $refs.preview.style.height = Math.min(12000, Math.max(400, Number($event.data.height))) + 'px';
        if ($event.data.type === 'proposal-edit') $wire.mountAction('editProposalElement', {key: $event.data.key});
    "
>
    <iframe x-ref="preview" wire:key="proposal-preview-{{ $this->proposalPreviewRevision }}"
        src="{{ $previewUrl }}?v={{ $this->proposalPreviewRevision }}"
        title="Proposal page preview" style="width:100%;height:2400px;border:0;border-radius:12px;display:block"
    ></iframe>
</div>
