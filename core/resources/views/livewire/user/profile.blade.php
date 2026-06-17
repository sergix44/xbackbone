<div class="grid grid-cols-12 gap-6">
    <div class="md:col-span-3 col-span-12">
        <x-menu class="rounded-lg bg-base-100" activate-by-route>
            <x-menu-item title="Profile" icon="o-user-circle" :link="route('user.profile')" exact/>
            <x-menu-item title="Tokens" icon="o-command-line" :link="route('user.profile', ['tab' => 'tokens'])"/>
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
