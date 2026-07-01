<div class="card bg-base-100">
    <div class="card-body">
        <h1 class="card-title">{{ __('Updates') }}</h1>

        <div class="divider">{{ __('Version') }}</div>

        <div class="flex flex-wrap items-center gap-6">
            <div class="flex items-center gap-2">
                <span class="text-sm opacity-70">{{ __('Current') }}</span>
                <x-badge :value="$current" class="badge-neutral"/>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-sm opacity-70">{{ __('Latest') }}</span>
                <x-badge :value="$latest ?? __('unknown')"
                         class="{{ $updateAvailable ? 'badge-warning' : 'badge-success' }}"/>
            </div>
        </div>

        @if(! $supported)
            <x-alert class="mt-4 alert-info" icon="o-information-circle"
                     :title="__('Self-upgrade is only available on production installations.')"/>
        @else
            @if($updateAvailable && $state !== 'running')
                <x-alert class="mt-4 alert-warning" icon="o-arrow-up-circle"
                         :title="__('A new version is available.')"
                         :description="__('You can upgrade from :current to :latest.', ['current' => $current, 'latest' => $latest])"/>
            @endif

            <div class="flex flex-wrap gap-2 mt-4">
                <x-button :label="__('Check for updates')" icon="o-arrow-path"
                          wire:click="checkNow" spinner="checkNow"
                          class="btn-outline" :disabled="$state === 'running'"/>

                @if($updateAvailable)
                    <x-button :label="__('Upgrade to latest')" icon="o-arrow-up-tray"
                              wire:click="startUpgrade" spinner="startUpgrade"
                              class="btn-primary" :disabled="$state === 'running'"
                              wire:confirm="{{ __('Upgrade now? The application may be briefly unavailable.') }}"/>
                @endif
            </div>

            @if($state === 'running')
                <div class="flex items-center gap-2 mt-4" wire:poll.2s="pollStatus">
                    <span class="loading loading-spinner loading-sm"></span>
                    <span>{{ __('Upgrade in progress...') }}</span>
                </div>
            @elseif($state === 'done')
                <x-alert class="mt-4 alert-success" icon="o-check-circle"
                         :title="__('Upgrade complete.')"/>
            @elseif($state === 'failed')
                <x-alert class="mt-4 alert-error" icon="o-x-circle"
                         :title="__('The last upgrade failed.')"/>
            @endif

            @if($logTail !== '')
                <div class="divider mt-8">{{ __('Log') }}</div>
                <pre class="bg-base-200 rounded-lg p-4 text-xs whitespace-pre-wrap overflow-x-auto max-h-96 overflow-y-auto">{{ $logTail }}</pre>
            @endif
        @endif
    </div>
</div>
