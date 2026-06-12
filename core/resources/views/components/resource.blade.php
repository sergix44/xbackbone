<div class="card bg-base-100 w-full shadow-sm">
    <div class="card-body px-3 py-2 gap-0">
        <div class="flex items-center gap-1 min-w-0">
            <a class="font-semibold text-sm truncate flex-1 min-w-0 hover:text-primary transition-colors"
               href="{{ $resource?->preview_ext_url }}" wire:navigate>
                {{ $resource?->filename ?? 'File Name' }}
            </a>
            <div class="inline-flex gap-0.5 shrink-0 ml-1">
                <x-button icon="m-link" class="btn-ghost btn-xs btn-square text-success" @click="$clipboard('{{$resource?->preview_ext_url}}')"/>
                <x-button icon="m-cloud-arrow-down" class="btn-ghost btn-xs btn-square text-info" :link="route('download', ['resource' => $resource->code])" no-wire-navigate external/>
                <x-button icon="m-eye-slash" class="btn-ghost btn-xs btn-square text-warning"/>
                <x-button icon="m-x-mark" class="btn-ghost btn-xs btn-square text-error"
                          wire:click="confirmDelete({{ $resource->id }})"/>
            </div>
        </div>
    </div>
    <figure>
        <a href="{{ $resource?->preview_ext_url }}" wire:navigate class="block w-full aspect-video bg-base-200 overflow-hidden">
            @if($resource->is_dir ?? false)
                <div class="w-full h-full flex items-center justify-center">
                    <x-icon name="o-folder" class="w-16 h-16 opacity-40"/>
                </div>
            @elseif($resource->has_preview || ($resource->type === \App\Models\Properties\ResourceType::IMAGE && $resource->is_displayable))
                <img src="{{ $resource->thumbnail_url }}?w=400" alt="{{ $resource->filename }}"
                     class="w-full h-full object-cover" loading="lazy"/>
            @else
                <div class="w-full h-full flex items-center justify-center">
                    @switch($resource->type)
                        @case(\App\Models\Properties\ResourceType::VIDEO)
                            <x-icon name="o-video-camera" class="w-16 h-16 opacity-40"/>
                            @break
                        @case(\App\Models\Properties\ResourceType::AUDIO)
                            <x-icon name="o-musical-note" class="w-16 h-16 opacity-40"/>
                            @break
                        @case(\App\Models\Properties\ResourceType::PDF)
                            <x-icon name="o-document-text" class="w-16 h-16 opacity-40"/>
                            @break
                        @case(\App\Models\Properties\ResourceType::LINK)
                            <x-icon name="o-link" class="w-16 h-16 opacity-40"/>
                            @break
                        @default
                            <x-icon name="o-document" class="w-16 h-16 opacity-40"/>
                    @endswitch
                </div>
            @endif
        </a>
    </figure>
    <div class="card-body px-3 py-2 gap-0">
        <div class="flex justify-between items-center text-xs text-base-content/50">
            <span class="font-mono">{{ $resource?->size_human_readable ?? '0' }}</span>
            <span class="tooltip tooltip-bottom" data-tip="{{ $resource?->created_at ?? '' }}">
                {{ $resource?->created_at?->diffForHumans() ?? '0' }}
            </span>
        </div>
    </div>
</div>
