<div class="grid grid-cols-2 @md:grid-cols-3 @2xl:grid-cols-4 gap-4 text-left">
    <div>
        <div class="text-xs uppercase opacity-60">Size</div>
        <div class="font-mono">{{ $resource->size_human_readable ?? '—' }}</div>
    </div>
    <div>
        <div class="text-xs uppercase opacity-60">Type</div>
        <div class="font-mono truncate" title="{{ $resource->mime }}">{{ $resource->mime ?? '—' }}</div>
    </div>
    @if($resource->type === \XBB\Models\Properties\ResourceType::IMAGE)
        <div>
            <div class="text-xs uppercase opacity-60">Dimensions</div>
            <div class="font-mono" x-text="naturalWidth ? `${naturalWidth} × ${naturalHeight}` : '—'">—</div>
        </div>
    @endif
    <div>
        <div class="text-xs uppercase opacity-60">Owner</div>
        <div class="truncate">{{ $resource->user?->name ?? '—' }}</div>
    </div>
    <div>
        <div class="text-xs uppercase opacity-60">Visibility</div>
        <div>{{ $resource->is_private ? __('Private') : __('Public') }}</div>
    </div>
    <div>
        <div class="text-xs uppercase opacity-60">Uploaded</div>
        <div class="tooltip tooltip-bottom" data-tip="{{ $resource->created_at }}">
            {{ $resource->created_at->diffForHumans() }}
        </div>
    </div>
    @if($resource->published_at)
        <div>
            <div class="text-xs uppercase opacity-60">Published</div>
            <div class="tooltip tooltip-bottom" data-tip="{{ $resource->published_at }}">
                {{ $resource->published_at->diffForHumans() }}
            </div>
        </div>
    @endif
    @if($resource->expires_at)
        <div>
            <div class="text-xs uppercase opacity-60">Expires</div>
            <div class="tooltip tooltip-bottom" data-tip="{{ $resource->expires_at }}">
                {{ $resource->expires_at->diffForHumans() }}
            </div>
        </div>
    @endif
    <div>
        <div class="text-xs uppercase opacity-60">Views</div>
        <div class="font-mono">{{ $resource->views }}</div>
    </div>
    <div>
        <div class="text-xs uppercase opacity-60">Downloads</div>
        <div class="font-mono">{{ $resource->downloads }}</div>
    </div>
</div>
