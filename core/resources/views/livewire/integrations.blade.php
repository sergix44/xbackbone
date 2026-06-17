@php
    $groups = [
        [
            'label' => 'Capture apps',
            'hint' => 'Install, then point them at your server.',
            'items' => [
                [
                    'name' => 'ShareX',
                    'icon' => 'si.sharex',
                    'platforms' => ['Windows'],
                    'description' => 'Free and open-source screen capture, file sharing, and productivity tool for Windows.',
                    'action' => 'Download config',
                    'route' => route('integrations.sharex'),
                ],
                [
                    'name' => 'ScreenCloud',
                    'icon' => 'o-cloud-arrow-up',
                    'platforms' => ['Windows', 'macOS', 'Linux'],
                    'description' => 'Open-source screen capture and file sharing app available across every desktop platform.',
                    'action' => 'Get extension',
                ],
            ],
        ],
        [
            'label' => 'Desktop scripts',
            'hint' => 'Drop-in scripts with native desktop integration.',
            'items' => [
                [
                    'name' => 'Linux Desktop',
                    'icon' => 'si.linux',
                    'platforms' => ['Linux'],
                    'description' => 'A lightweight upload script that hooks straight into your desktop environment.',
                    'action' => 'Download script',
                ],
                [
                    'name' => 'KDE',
                    'icon' => 'si.kde',
                    'platforms' => ['Linux', 'KDE'],
                    'description' => 'Upload script with native KDE desktop integration for one-click sharing.',
                    'action' => 'Download script',
                ],
            ],
        ],
    ];
@endphp

<div class="max-w-5xl mx-auto">
    <div class="mb-8">
        <h1 class="flex items-center gap-2 text-2xl font-extrabold">
            <x-icon name="o-puzzle-piece" class="size-7 text-primary"/>
            Integrations
        </h1>
        <p class="mt-1 text-sm text-base-content/60">
            Connect your favorite capture tools and upload straight to your server.
        </p>
    </div>

    <div class="flex flex-col gap-10">
        @foreach($groups as $group)
            <section>
                <div class="mb-3 flex items-baseline justify-between gap-4">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-base-content/50">
                        {{ $group['label'] }}
                    </h2>
                    <span class="text-xs text-base-content/40">{{ $group['hint'] }}</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach($group['items'] as $integration)
                        <div class="group card border border-base-content/10 bg-base-100 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-md">
                            <div class="card-body gap-4">
                                <div class="flex items-start gap-4">
                                    <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-base-200 text-base-content transition-colors group-hover:bg-primary/10 group-hover:text-primary">
                                        <x-icon name="{{ $integration['icon'] }}" class="size-6"/>
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="text-lg font-semibold leading-tight">{{ $integration['name'] }}</h3>
                                        <div class="mt-1.5 flex flex-wrap gap-1">
                                            @foreach($integration['platforms'] as $platform)
                                                <span class="badge badge-sm badge-ghost">{{ $platform }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <p class="grow text-sm text-base-content/70">
                                    {{ $integration['description'] }}
                                </p>

                                <div class="card-actions">
                                    @if(! empty($integration['route']))
                                        <x-button :label="$integration['action']" icon="o-arrow-down-tray" class="btn-primary btn-block" :link="$integration['route']" no-wire-navigate/>
                                    @else
                                        <x-button :label="$integration['action']" icon="o-arrow-down-tray" class="btn-primary btn-block" disabled/>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</div>
