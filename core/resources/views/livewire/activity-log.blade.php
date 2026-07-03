<div>
    @php
        $categoryOptions = collect(\XBB\Support\ActivityEvent::categories())
            ->map(fn ($label, $value) => ['id' => $value, 'name' => $label])
            ->prepend(['id' => '', 'name' => __('All activity')])
            ->values()
            ->all();
    @endphp

    <div class="card bg-base-100">
        <div class="card-body">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <h1 class="card-title flex-1">{{ __('Activity Log') }}</h1>
                @if($this->isGlobal())
                    <x-input placeholder="{{ __('Search by user...') }}" wire:model.live.debounce.300ms="search"
                             icon="o-magnifying-glass" clearable class="w-full sm:w-56"/>
                @endif
                <x-select :options="$categoryOptions" wire:model.live="category" icon="o-funnel" class="w-full sm:w-48"/>
            </div>

            <div class="mt-4 flex flex-col gap-2">
                @forelse($this->activities as $activity)
                    @php
                        $meta = \XBB\Support\ActivityEvent::describe($activity->description);
                        [$textClass, $bgClass] = match ($meta['color']) {
                            'success' => ['text-success', 'bg-success/10'],
                            'error' => ['text-error', 'bg-error/10'],
                            'warning' => ['text-warning', 'bg-warning/10'],
                            'info' => ['text-info', 'bg-info/10'],
                            'primary' => ['text-primary', 'bg-primary/10'],
                            default => ['text-base-content/70', 'bg-base-300'],
                        };
                        $subjectLabel = \XBB\Support\ActivityEvent::subjectLabel($activity);
                        $subjectUrl = \XBB\Support\ActivityEvent::subjectUrl($activity);
                        $email = $activity->properties?->get('email');
                    @endphp
                    <div class="flex items-center gap-3 rounded-lg border border-base-300 px-4 py-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $bgClass }}">
                            <x-icon :name="$meta['icon']" class="h-5 w-5 {{ $textClass }}"/>
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="font-medium">{{ __($meta['label']) }}</div>
                            <div class="flex flex-wrap items-center gap-x-1.5 text-xs opacity-60">
                                @if($this->isGlobal())
                                    <span class="truncate">{{ $activity->causer?->name ?? __('System') }}</span>
                                @endif
                                @if($subjectLabel)
                                    @if($this->isGlobal())<span>·</span>@endif
                                    @if($subjectUrl)
                                        <a href="{{ $subjectUrl }}" class="link link-hover truncate">{{ $subjectLabel }}</a>
                                    @else
                                        <span class="truncate">{{ $subjectLabel }}</span>
                                    @endif
                                @elseif($email)
                                    @if($this->isGlobal())<span>·</span>@endif
                                    <span class="truncate">{{ $email }}</span>
                                @endif
                            </div>
                        </div>
                        <span class="tooltip tooltip-left shrink-0 text-xs opacity-60" data-tip="{{ $activity->created_at }}">
                            {{ $activity->created_at?->diffForHumans() }}
                        </span>
                    </div>
                @empty
                    <x-alert title="{{ __('No activity recorded yet.') }}" icon="o-information-circle"/>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $this->activities->links() }}
            </div>
        </div>
    </div>
</div>
