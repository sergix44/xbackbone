@section('menu-items')
    <x-button label="Copy link" icon="o-link" class="btn-sm btn-soft btn-success" @click="$clipboard('{{ $resource->preview_ext_url }}')"/>
    <x-button label="Download" icon="o-cloud-arrow-down" class="btn-sm btn-soft btn-info" link="{{ $resource->download_url }}" external no-wire-navigate/>
    <x-button label="Original" icon="o-eye" class="btn-sm btn-soft" link="{{ $resource->raw_url }}" external no-wire-navigate/>
@endsection

<div class="flex flex-col items-center gap-6"
     x-data="{
         contentWidth: null,
         naturalWidth: null,
         naturalHeight: null,
         observer: null,
         measure() { this.contentWidth = this.$refs.media?.offsetWidth ?? null },
         onLoad() {
             this.naturalWidth = this.$refs.media?.naturalWidth ?? null;
             this.naturalHeight = this.$refs.media?.naturalHeight ?? null;
             this.measure();
         },
         init() {
             this.$nextTick(() => {
                 this.measure();
                 if (this.$refs.media) {
                     this.observer = new ResizeObserver(() => this.measure());
                     this.observer.observe(this.$refs.media);
                 }
             });
         },
         destroy() { this.observer?.disconnect(); },
     }">
    {{-- MEDIA: fills the available space above the fold --}}
    <div class="flex items-center justify-center w-full min-h-[calc(100dvh-8rem)]">
        @if($resource->is_displayable)
            @switch($resource->type)
                @case(\App\Models\Properties\ResourceType::IMAGE)
                    <a href="{{ $resource->raw_url }}" target="_blank">
                        <img x-ref="media" @load="onLoad()" src="{{ $resource->raw_url }}"
                             alt="{{ $resource->filename ?? $resource->code }}"
                             class="block max-h-[calc(100dvh-8rem)] max-w-full rounded-box shadow-sm"/>
                    </a>
                    @break

                @case(\App\Models\Properties\ResourceType::VIDEO)
                    <div x-ref="media"
                         class="rounded-box overflow-hidden shadow-sm bg-black">
                        <div x-data="plyrPlayer()">
                            <video x-ref="video" playsinline class="w-full">
                                <source src="{{ $resource->raw_url }}" type="{{ $resource->mime }}">
                            </video>
                        </div>
                    </div>
                    @break

                @case(\App\Models\Properties\ResourceType::PDF)
                    <object x-ref="media"
                            type="{{ $resource->mime }}"
                            data="{{ $resource->raw_url }}"
                            class="w-full max-w-7xl h-[calc(100dvh-8rem)] rounded-box shadow-sm bg-base-100">
                        <div class="flex flex-col items-center gap-4 p-8 opacity-70">
                            <x-icon name="o-document" class="w-24 h-24"/>
                            <p>{{ __('Your browser does not support PDF previews.') }}</p>
                            <x-button label="Download" icon="o-cloud-arrow-down" class="btn-soft btn-info"
                                      link="{{ $resource->download_url }}" external no-wire-navigate/>
                        </div>
                    </object>
                    @break

                @case(\App\Models\Properties\ResourceType::TEXT)
                    @if($this->textTooLarge)
                        <div x-ref="media" class="flex flex-col items-center gap-4 p-8 opacity-70">
                            <x-icon name="o-document-text" class="w-24 h-24"/>
                            <p>{{ __('This file is too large to preview.') }}</p>
                            <x-button label="Download" icon="o-cloud-arrow-down" class="btn-soft btn-info"
                                      link="{{ $resource->download_url }}" external no-wire-navigate/>
                        </div>
                    @else
                        @php($text = $this->textContent)
                        @php($lineCount = max(1, substr_count($text, "\n") + (str_ends_with($text, "\n") ? 0 : 1)))
                        <div x-ref="media" x-data="codeHighlighter('{{ $resource->extension }}')"
                             class="w-full rounded-box shadow-sm overflow-hidden bg-base-100">
                            <div class="flex items-start overflow-y-auto max-h-[calc(100dvh-8rem)] font-mono text-sm leading-relaxed">
                                <div aria-hidden="true"
                                     class="shrink-0 select-none py-4 pl-4 pr-3 text-right tabular-nums opacity-40 border-r border-base-content/10">
                                    @for($i = 1; $i <= $lineCount; $i++)
                                        <div>{{ $i }}</div>
                                    @endfor
                                </div>
                                <pre class="flex-1 min-w-0 overflow-x-auto py-4 px-4"><code x-ref="code">{{ $text }}</code></pre>
                            </div>
                        </div>
                    @endif
                    @break

                @case(\App\Models\Properties\ResourceType::AUDIO)
                    <div x-ref="media" class="w-full max-w-7xl">
                        <div x-data="wavesurferPlayer('{{ $resource->raw_url }}')"
                             class="card bg-base-100 shadow-xl">
                            <div class="card-body gap-6">
                                <div x-ref="waveform" class="text-primary w-full"></div>
                                <div class="flex items-center gap-4">
                                    <button @click="toggle()" :disabled="loading"
                                            class="btn btn-circle btn-primary">
                                        <span x-show="loading" class="loading loading-spinner loading-sm"></span>
                                        <span x-show="!loading && !playing"><x-icon name="o-play" class="w-5 h-5"/></span>
                                        <span x-show="!loading && playing" x-cloak><x-icon name="o-pause" class="w-5 h-5"/></span>
                                    </button>
                                    <span class="font-mono text-sm opacity-70"
                                          x-text="`${currentTime} / ${duration}`">—</span>
                                    <div class="ml-auto flex items-center gap-2 opacity-70">
                                        <button @click="toggleMute()" class="btn btn-ghost btn-sm btn-circle">
                                            <span x-show="volume > 0"><x-icon name="o-speaker-wave" class="w-4 h-4"/></span>
                                            <span x-show="volume === 0" x-cloak><x-icon name="o-speaker-x-mark" class="w-4 h-4"/></span>
                                        </button>
                                        <input type="range" min="0" max="1" step="0.05"
                                               x-model.number="volume"
                                               class="range range-xs range-primary w-24"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @break
            @endswitch
        @else
            <div x-ref="media" class="flex flex-col items-center gap-2 opacity-60">
                <x-icon name="o-document" class="w-24 h-24"/>
                <p>No preview available for this file.</p>
            </div>
        @endif
    </div>

    {{-- INFO: details below the fold --}}
    <div class="card @container bg-base-100 w-full min-w-[min(100%,28rem)] shadow-sm"
         :style="contentWidth ? `width: ${contentWidth}px` : null">
        <div class="card-body">
            <h2 class="card-title break-all">{{ $resource->filename ?? $resource->code }}</h2>
            <div class="mt-2 grid grid-cols-2 @md:grid-cols-3 @2xl:grid-cols-4 gap-4">
                <div>
                    <div class="text-xs uppercase opacity-60">Size</div>
                    <div class="font-mono">{{ $resource->size_human_readable ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase opacity-60">Type</div>
                    <div class="font-mono truncate" title="{{ $resource->mime }}">{{ $resource->mime ?? '—' }}</div>
                </div>
                @if($resource->type === \App\Models\Properties\ResourceType::IMAGE)
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
        </div>
    </div>
</div>

@script
<script>
    Livewire.on('clipboard:copied', ({text}) => {
        $wire.$call('success', 'Copied to clipboard', text);
    });
</script>
@endscript
