@props(['resource'])

<div {{ $attributes->class('card bg-base-100 w-full shadow-sm') }}>
    <div class="card-body px-3 py-2 gap-0">
        <div class="flex items-center gap-1 min-w-0">
            <a class="font-semibold text-sm truncate flex-1 min-w-0 hover:text-primary transition-colors"
               href="{{ $resource?->preview_ext_url }}" wire:navigate>
                {{ $resource?->display_name ?? 'File Name' }}
            </a>
            <div class="inline-flex gap-0.5 shrink-0 ml-1">
                <x-button icon="m-link" class="btn-ghost btn-xs btn-square text-success" @click="$clipboard('{{$resource?->preview_ext_url}}')"/>
                @if($resource->type === \App\Models\Properties\ResourceType::LINK)
                    <x-button icon="m-arrow-top-right-on-square" class="btn-ghost btn-xs btn-square text-info" :link="$resource->raw_url" no-wire-navigate external/>
                @else
                    <x-button icon="m-cloud-arrow-down" class="btn-ghost btn-xs btn-square text-info" :link="route('download', ['resource' => $resource->code])" no-wire-navigate external/>
                @endif
                <x-button icon="{{ $resource->is_private ? 'm-eye' : 'm-eye-slash' }}"
                          class="btn-ghost btn-xs btn-square {{ $resource->is_private ? 'text-success' : 'text-warning' }}"
                          tooltip="{{ $resource->is_private ? __('Publish') : __('Hide') }}"
                          wire:click="toggleVisibility({{ $resource->id }})"/>
                <x-button icon="m-x-mark" class="btn-ghost btn-xs btn-square text-error"
                          wire:click="confirmDelete({{ $resource->id }})"/>
            </div>
        </div>
    </div>
    <figure>
        <a href="{{ $resource?->preview_ext_url }}" wire:navigate class="block w-full aspect-video bg-base-200 overflow-hidden">
            @if($resource->has_preview || ($resource->type === \App\Models\Properties\ResourceType::IMAGE && $resource->is_displayable))
                <img src="{{ $resource->thumbnail_url }}?w=400" alt="{{ $resource->filename }}"
                     class="w-full h-full object-cover" loading="lazy"/>
            @elseif($resource->preview_is_pending)
                <div x-data="pendingPreview('{{ $resource->thumbnail_url }}?w=400')"
                     class="relative w-full h-full flex items-center justify-center bg-gradient-to-br from-base-200 to-base-300">
                    <div x-show="!ready"
                         class="flex items-center justify-center w-24 h-24 rounded-2xl bg-base-100/60 shadow-sm ring-1 ring-base-content/5"
                         :class="{ 'animate-pulse': !settled }">
                        <x-icon name="{{ $resource->icon }}" class="w-14 h-14 {{ $resource->icon_color }}"/>
                    </div>
                    <img x-show="ready" x-cloak :src="src" alt="{{ $resource->filename }}"
                         class="absolute inset-0 w-full h-full object-cover"/>
                </div>
            @else
                <div class="group w-full h-full flex items-center justify-center bg-gradient-to-br from-base-200 to-base-300">
                    <div class="flex items-center justify-center w-24 h-24 rounded-2xl bg-base-100/60 shadow-sm ring-1 ring-base-content/5 transition-transform duration-200 group-hover:scale-105">
                        <x-icon name="{{ $resource->icon }}" class="w-14 h-14 {{ $resource->icon_color }}"/>
                    </div>
                </div>
            @endif
        </a>
    </figure>
    <div class="card-body px-3 py-2 gap-0">
        <div class="flex justify-between items-center text-xs text-base-content/50">
            <span class="font-mono truncate">
                @if($resource->type === \App\Models\Properties\ResourceType::LINK)
                    {{ parse_url($resource->data, PHP_URL_HOST) ?? __('Link') }}
                @else
                    {{ $resource?->size_human_readable ?? '0' }}
                @endif
            </span>
            <span class="tooltip tooltip-bottom" data-tip="{{ $resource?->created_at ?? '' }}">
                {{ $resource?->created_at?->diffForHumans() ?? '0' }}
            </span>
        </div>
    </div>
</div>
