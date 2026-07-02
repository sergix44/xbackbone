<div class="grid grid-cols-12 gap-6">
    <div class="md:col-span-3 col-span-12">
        <x-menu class="rounded-lg bg-base-100" activate-by-route>
            <x-menu-item title="Profile" icon="o-user-circle" :link="route('user.profile')" exact/>
            <x-menu-item title="Tokens" icon="o-command-line" :link="route('user.profile', ['tab' => 'tokens'])"/>
            <x-menu-item title="Passkeys" icon="o-finger-print" :link="route('user.profile', ['tab' => 'passkeys'])"/>
            <x-menu-item title="Activity" icon="o-clock" :link="route('user.profile', ['tab' => 'activity'])"/>
            <x-menu-item title="Export Data" icon="o-arrow-right-start-on-rectangle" :link="route('user.profile', ['tab' => 'export'])"/>
            <x-menu-item title="Delete Account" icon="o-user-minus" class="text-red-500" :link="route('user.profile', ['tab' => 'delete'])"/>
        </x-menu>
    </div>
    <div class="md:col-span-9 col-span-12 flex flex-col gap-2">
        @if($tab === 'tokens')
            <div class="card bg-base-100">
                <div class="card-body">
                    <h1 class="card-title">Account Tokens</h1>
                    @php
                        $headers = [
                            ['key' => 'id', 'label' => '#'],
                            ['key' => 'name', 'label' => 'Name'],
                            ['key' => 'last_used_at', 'label' => 'Last Used', 'format' => (static fn($row, $field) => $field ? $field->diffForHumans() : 'Never')],
                            ['key' => 'abilities', 'label' => 'Abilities', 'format' => (static fn($row, $field) => implode(',', $field))],
                        ];
                    @endphp
                    <x-table :headers="$headers" :rows="$user->tokens" wire:model="selectedTokens" striped selectable/>
                    <div class="mt-4">
                        <x-button class="btn-primary" label="Revoke Tokens" icon="o-trash" wire:click="revokeSelectedTokens" spinner/>
                    </div>
                </div>
            </div>
        @elseif($tab === 'passkeys')
            <div class="card bg-base-100">
                <div class="card-body">
                    <h1 class="card-title">{{ __('Passkeys') }}</h1>
                    <p class="text-sm opacity-70">
                        {{ __('Passkeys let you sign in without a password using your device biometrics or a security key.') }}
                    </p>

                    <div class="mt-4 flex flex-col gap-3">
                        @forelse($this->passkeys as $passkey)
                            <div class="flex items-center justify-between rounded-lg border border-base-300 px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <x-icon name="o-finger-print" class="w-5 h-5 text-primary"/>
                                    <div>
                                        <div class="font-semibold">{{ $passkey->name }}</div>
                                        <div class="text-xs opacity-60">
                                            @if($passkey->authenticator){{ $passkey->authenticator }} · @endif
                                            {{ __('added :date', ['date' => $passkey->created_at->diffForHumans()]) }}
                                            @if($passkey->last_used_at)
                                                · {{ __('last used :date', ['date' => $passkey->last_used_at->diffForHumans()]) }}
                                            @else
                                                · {{ __('never used') }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <x-button icon="o-trash" class="btn-ghost btn-sm text-error"
                                          wire:click="deletePasskey({{ $passkey->id }})"
                                          wire:confirm="{{ __('Remove this passkey?') }}"
                                          spinner="deletePasskey({{ $passkey->id }})"/>
                            </div>
                        @empty
                            <p class="text-sm opacity-60">{{ __('You have not registered any passkeys yet.') }}</p>
                        @endforelse
                    </div>

                    <div class="divider mt-6">{{ __('Add a passkey') }}</div>
                    <div x-data="passkeyManager()">
                        <template x-if="!supported">
                            <div class="alert alert-warning text-sm">
                                {{ __('Your browser does not support passkeys.') }}
                            </div>
                        </template>
                        <div x-show="supported" x-cloak class="flex flex-col gap-2 sm:flex-row sm:items-end">
                            <x-input :label="__('Name')" :placeholder="__('e.g. MacBook Touch ID')" x-model="name"
                                     @keydown.enter.prevent="register()" class="w-full"/>
                            <x-button :label="__('Add a passkey')" icon="o-plus" class="btn-primary"
                                      @click="register()" ::disabled="busy"/>
                        </div>
                        <p x-show="error" x-text="error" x-cloak class="text-error text-sm mt-2"></p>
                    </div>
                </div>
            </div>
        @elseif($tab === 'activity')
            <livewire:activity-log :causer-id="$user->id"/>
        @elseif($tab === 'export')
            <div class="card bg-base-100">
                <div class="card-body">
                    <h1 class="card-title">{{ __('Export Data') }}</h1>
                    <p class="text-sm opacity-70">
                        {{ __('Download a ZIP archive containing every file you have uploaded. The archive is generated on the fly and streamed straight to your browser.') }}
                    </p>
                    <div class="mt-2 flex flex-wrap items-center gap-x-5 gap-y-1 text-xs">
                        <span class="inline-flex items-center gap-1.5">
                            <x-icon name="o-photo" class="w-4 h-4 text-success"/>
                            <span class="font-semibold text-base-content">{{ $this->stats['media'] }}</span>
                            <span class="opacity-60">{{ __('files') }}</span>
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <x-icon name="o-circle-stack" class="w-4 h-4 text-warning"/>
                            <span class="font-semibold text-base-content">{{ $this->stats['size'] }}</span>
                        </span>
                    </div>
                    <div class="mt-4">
                        <x-button :label="__('Download my data')" icon="o-arrow-down-tray" class="btn-primary" :link="route('user.profile.export')" external/>
                    </div>
                </div>
            </div>
        @elseif($tab === 'delete')
            <div class="card bg-base-100">
                <div class="card-body">
                    <h1 class="card-title text-error">{{ __('Delete Account') }}</h1>
                    <p class="text-sm opacity-70">
                        {{ __('This permanently deletes your account, all the files you have uploaded and your API tokens. This action cannot be undone.') }}
                    </p>
                    <div class="mt-4">
                        <x-button :label="__('Delete my account')" icon="o-user-minus" class="btn-error" @click="$wire.confirmingDelete = true"/>
                    </div>
                </div>
            </div>

            <x-modal wire:model="confirmingDelete" :title="__('Delete account')" :subtitle="__('This action cannot be undone.')" separator>
                <p class="mb-4">{{ __('Enter your password to confirm.') }}</p>
                <x-input :label="__('Password')" type="password" wire:model="deletePassword" error-field="deletePassword"/>
                <x-slot:actions>
                    <x-button :label="__('Cancel')" @click="$wire.confirmingDelete = false"/>
                    <x-button :label="__('Delete account')" class="btn-error" icon="o-user-minus" wire:click="deleteAccount" spinner="deleteAccount"/>
                </x-slot:actions>
            </x-modal>
        @else
            <div class="card bg-base-100">
                <div class="card-body">
                    <x-avatar :image="$user->avatar" class="!w-22">
                        <x-slot:title class="text-3xl !font-bold pl-2">
                            {{ $name }}
                        </x-slot:title>

                        <x-slot:subtitle class="flex flex-wrap items-center gap-x-5 gap-y-1 mt-3 pl-2">
                            <span class="inline-flex items-center gap-1.5 text-xs">
                                <x-icon name="o-photo" class="w-4 h-4 text-success"/>
                                <span class="font-semibold text-base-content">{{ $this->stats['media'] }}</span>
                                <span class="opacity-60">{{ __('media') }}</span>
                            </span>
                            <span class="inline-flex items-center gap-1.5 text-xs">
                                <x-icon name="o-circle-stack" class="w-4 h-4 text-warning"/>
                                <span class="font-semibold text-base-content">{{ $this->stats['size'] }}</span>
                                @unless($this->stats['quota_unlimited'])
                                    <span class="opacity-60">/ {{ $this->stats['quota'] }}</span>
                                @endunless
                            </span>
                            <span class="inline-flex items-center gap-1.5 text-xs">
                                <x-icon name="o-eye" class="w-4 h-4 text-info"/>
                                <span class="font-semibold text-base-content">{{ $this->stats['views'] }}</span>
                                <span class="opacity-60">{{ __('views') }}</span>
                            </span>
                            <span class="inline-flex items-center gap-1.5 text-xs">
                                <x-icon name="o-arrow-down-tray" class="w-4 h-4 text-primary"/>
                                <span class="font-semibold text-base-content">{{ $this->stats['downloads'] }}</span>
                                <span class="opacity-60">{{ __('downloads') }}</span>
                            </span>
                        </x-slot:subtitle>
                    </x-avatar>
                    @unless($this->stats['quota_unlimited'])
                        <div class="mt-4">
                            <div class="flex items-center justify-between text-xs text-base-content/70 mb-1">
                                <span>{{ __('Storage quota') }}</span>
                                <span class="font-semibold text-base-content">
                                    {{ $this->stats['size'] }} / {{ $this->stats['quota'] }} ({{ $this->stats['quota_percent'] }}%)
                                </span>
                            </div>
                            <progress class="progress {{ $this->stats['quota_percent'] >= 90 ? 'progress-error' : 'progress-primary' }} w-full"
                                      max="100" value="{{ $this->stats['quota_percent'] }}"></progress>
                        </div>
                    @endunless
                    <div class="divider mt-8">Profile</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input placeholder="Username" label="Username" type="text" wire:model="name" error-field="name" inline/>
                        <x-input placeholder="E-mail" label="E-mail" type="email" wire:model="email" error-field="email" inline/>

                        <x-input placeholder="Current password" label="Current password" type="password" wire:model="currentPassword" error-field="current_password" inline/>
                        <x-input placeholder="New password" label="New password" type="password" wire:model="newPassword" error-field="password" inline/>
                    </div>
                    <div class="mt-3 text-xs">
                        @if($user->hasVerifiedEmail())
                            <span class="inline-flex items-center gap-1 text-success">
                                <x-icon name="o-check-badge" class="w-4 h-4"/>{{ __('Your email address is verified.') }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-warning">
                                <x-icon name="o-exclamation-triangle" class="w-4 h-4"/>{{ __('Your email address is not verified.') }}
                                <button type="button" class="link link-primary" wire:click="resendVerification">{{ __('Resend verification email') }}</button>
                            </span>
                        @endif
                    </div>
                    <div class="mt-4">
                        <x-button label="Save" icon="o-check-circle" class="btn-primary" wire:click="updateProfile()" spinner/>
                    </div>
                    <div class="divider mt-8">Theme</div>
                    <div class="grid grid-cols-1 gap-4">
                        <x-select :value="$theme" icon="o-paint-brush" :options="$themes" wire:model="theme" wire:change="updateTheme()" inline/>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
