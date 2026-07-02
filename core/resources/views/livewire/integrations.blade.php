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
                    'link' => 'https://getsharex.com/',
                ],
                [
                    'name' => 'Xerahs',
                    'icon' => 'o-camera',
                    'platforms' => ['Windows', 'macOS', 'Linux'],
                    'description' => 'ShareX reimagined with modern UI, built from the ground up for cross-platform performance.',
                    'action' => 'Download config',
                    'route' => route('integrations.xerahs'),
                    'link' => 'https://xerahs.com',
                ],
                [
                    'name' => 'ScreenCloud',
                    'icon' => 'o-cloud-arrow-up',
                    'platforms' => ['Windows', 'macOS', 'Linux'],
                    'description' => 'Open-source screen capture and file sharing app available across every desktop platform.',
                    'action' => 'Copy install link',
                    'link' => 'https://screencloud.net',
                    'copy' => \Illuminate\Support\Facades\URL::signedRoute('integrations.screencloud', ['user' => auth()->id()]),
                ],
                [
                    'name' => 'ishare',
                    'icon' => 'o-viewfinder-circle',
                    'platforms' => ['macOS'],
                    'description' => 'A clean, unbloated screen capture and file sharing utility for macOS with custom uploader support.',
                    'action' => 'Download config',
                    'route' => route('integrations.ishare'),
                    'link' => 'https://isharemac.app/',
                ],
            ],
        ],
        [
            'label' => 'System integrations',
            'hint' => 'Extend your desktop experience with seamless integration.',
            'items' => [
                [
                    'name' => 'KDE',
                    'icon' => 'si.kde',
                    'platforms' => ['Linux'],
                    'description' => "A Purpose plugin that adds an upload entry to the KDE Share menu in Spectacle, Dolphin and other apps.",
                    'action' => 'Download package',
                    'route' => route('integrations.kde'),
                    'link' => 'https://apps.kde.org/spectacle/',
                ],
                [
                    'name' => 'macOS',
                    'icon' => 'si.apple',
                    'platforms' => ['macOS'],
                    'description' => 'A Shortcut that adds an upload entry to the macOS Share sheet, the Services menu and the Shortcuts app.',
                    'action' => 'Download installer',
                    'route' => route('integrations.macos'),
                    'link' => 'https://support.apple.com/guide/shortcuts-mac/welcome/mac',
                ],
            ],
        ],
        [
            'label' => 'CLI scripts',
            'hint' => 'Drop-in shell scripts you run straight from the terminal.',
            'items' => [
                [
                    'name' => 'CLI',
                    'icon' => 'o-command-line',
                    'platforms' => ['Linux', 'macOS'],
                    'description' => 'A portable shell uploader for your terminal: upload files or piped text, with clipboard, screenshot, and scripting-friendly output.',
                    'action' => 'Download script',
                    'route' => route('integrations.cli'),
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
                                    @if(! empty($integration['link']))
                                        <a href="{{ $integration['link'] }}" target="_blank" rel="noopener noreferrer"
                                           class="ml-auto shrink-0 text-base-content/40 transition-colors hover:text-primary"
                                           title="Visit {{ $integration['name'] }} website">
                                            <x-icon name="o-arrow-top-right-on-square" class="size-4"/>
                                        </a>
                                    @endif
                                </div>

                                <p class="grow text-sm text-base-content/70">
                                    {{ $integration['description'] }}
                                </p>

                                <div class="card-actions">
                                    @if(! empty($integration['copy']))
                                        <div class="w-full" x-data="{ copied: false, link: @js($integration['copy']) }">
                                            <x-button :label="$integration['action']" icon="o-clipboard-document" class="btn-primary btn-block"
                                                      x-on:click="navigator.clipboard.writeText(link); copied = true; setTimeout(() => copied = false, 2000)"/>
                                            <p class="mt-1.5 text-center text-xs text-success" x-show="copied" x-cloak>
                                                Link copied — paste it into ScreenCloud
                                            </p>
                                        </div>
                                    @elseif(! empty($integration['route']))
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
