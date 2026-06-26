<div>
    <x-card shadow>
        <x-steps wire:model="step" steps-color="step-primary" class="mb-8" stepper-classes="w-full">
            {{-- Step 1: application --}}
            <x-step :step="1" :text="__('Application')">
                <div class="divider"></div>
                <x-input :label="__('Application URL')" wire:model="appUrl" error-field="appUrl" icon="o-globe-alt" hint="Confirm the public URL of your installation."/>

                <div class="mt-6">
                    <h3 class="font-semibold">{{ __('Server requirements') }}</h3>
                    <p class="text-xs opacity-70 mb-3">
                        {{ __('Review the server requirements and make sure they are met.') }}
                    </p>
                    <ul class="space-y-2">
                        @foreach($this->requirements as $requirement)
                            <li class="flex items-center gap-2 text-sm">
                                @if($requirement['ok'])
                                    <x-icon name="o-check-circle" class="text-success w-5 h-5"/>
                                @else
                                    <x-icon name="o-x-circle" class="text-error w-5 h-5"/>
                                @endif
                                <span>{{ $requirement['label'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </x-step>

            {{-- Step 2: database --}}
            <x-step :step="2" :text="__('Database')">
                <div class="divider"></div>
                <x-select :label="__('Database driver')" :options="$this->databaseDrivers" wire:model.live="dbDriver" error-field="dbDriver"/>

                @if($dbDriver === 'sqlite')
                    <x-input :label="__('Database file path')" wire:model.blur="dbSqlitePath" error-field="dbSqlitePath" />
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <x-input :label="__('Host')" wire:model.blur="dbHost" error-field="dbHost"/>
                        <x-input :label="__('Port')" type="number" wire:model.blur="dbPort" error-field="dbPort"/>
                        <x-input :label="__('Database name')" wire:model.blur="dbDatabase" error-field="dbDatabase"/>
                        <x-input :label="__('Username')" wire:model.blur="dbUsername" error-field="dbUsername"/>
                        <x-input :label="__('Password')" type="password" wire:model.blur="dbPassword" error-field="dbPassword" class="md:col-span-2"/>
                    </div>
                @endif

                <div class="flex items-center gap-3 mt-4">
                    <x-button :label="__('Test connection')" icon="o-bolt" class="btn-soft" wire:click="testDatabase" spinner="testDatabase"/>
                    @if($dbConnectionVerified)
                        <x-badge :value="__('Connection OK')" class="badge-success"/>
                    @endif
                </div>
            </x-step>

            {{-- Step 3: storage --}}
            <x-step :step="3" :text="__('Storage')">
                <div class="divider"></div>
                <x-select :label="__('Storage driver')" :options="$this->storageDrivers" wire:model.live="storageDriver" error-field="storageDriver"/>

                @if($storageDriver === 'local')
                    <x-input :label="__('Storage root path')" wire:model.blur="localRoot" error-field="localRoot"/>
                @elseif($storageDriver === 's3')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <x-input :label="__('Access key ID')" wire:model.blur="s3Key" error-field="s3Key"/>
                        <x-input :label="__('Secret access key')" type="password" wire:model.blur="s3Secret" error-field="s3Secret"/>
                        <x-input :label="__('Region')" wire:model.blur="s3Region" error-field="s3Region"/>
                        <x-input :label="__('Bucket')" wire:model.blur="s3Bucket" error-field="s3Bucket"/>
                        <x-input :label="__('Endpoint (optional)')" wire:model.blur="s3Endpoint" error-field="s3Endpoint" class="md:col-span-2"/>
                        <x-checkbox :label="__('Use path-style endpoint')" wire:model="s3PathStyle" class="md:col-span-2"/>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <x-input :label="__('Host')" wire:model.blur="ftpHost" error-field="ftpHost"/>
                        <x-input :label="__('Port')" type="number" wire:model.blur="ftpPort" error-field="ftpPort"/>
                        <x-input :label="__('Username')" wire:model.blur="ftpUsername" error-field="ftpUsername"/>
                        <x-input :label="__('Password')" type="password" wire:model.blur="ftpPassword" error-field="ftpPassword"/>
                        <x-input :label="__('Root path (optional)')" wire:model.blur="ftpRoot" error-field="ftpRoot" class="md:col-span-2"/>
                    </div>
                @endif

                <div class="flex items-center gap-3 mt-4">
                    <x-button :label="__('Test storage')" icon="o-bolt" class="btn-soft" wire:click="testStorage" spinner="testStorage"/>
                    @if($storageVerified)
                        <x-badge :value="__('Storage OK')" class="badge-success"/>
                    @endif
                </div>
            </x-step>

            {{-- Step 4: admin --}}
            <x-step :step="4" :text="__('Administrator')">
                <div class="divider"></div>
                <p class="text-sm opacity-70 mb-4">{{ __('Create the first administrator account.') }}</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input :label="__('Name')" wire:model.blur="name" error-field="name" class="md:col-span-2"/>
                    <x-input :label="__('Email')" type="email" wire:model.blur="email" error-field="email" class="md:col-span-2"/>
                    <x-input :label="__('Password')" type="password" wire:model.blur="password" error-field="password"/>
                    <x-input :label="__('Confirm password')" type="password" wire:model.blur="password_confirmation" error-field="password_confirmation"/>
                </div>
            </x-step>

            {{-- Step 5: legacy import --}}
            <x-step :step="5" :text="__('Import')">
                <div class="divider"></div>
                <x-toggle :label="__('Import data from an existing XBackBone 3 instance')" wire:model.live="importLegacy"/>

                @if($importLegacy)
                    <div class="mt-4 space-y-4">
                        <x-select :label="__('Legacy database type')" :options="$this->legacyDrivers" wire:model.live="legacyDriver" error-field="legacyDriver"/>

                        @if($legacyDriver === 'sqlite')
                            <x-input :label="__('Legacy SQLite file path')" wire:model.blur="legacyDbFile" error-field="legacyDbFile"/>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <x-input :label="__('Host')" wire:model.blur="legacyDbHost" error-field="legacyDbHost"/>
                                <x-input :label="__('Port')" type="number" wire:model.blur="legacyDbPort" error-field="legacyDbPort"/>
                                <x-input :label="__('Database name')" wire:model.blur="legacyDbDatabase" error-field="legacyDbDatabase"/>
                                <x-input :label="__('Username')" wire:model.blur="legacyDbUsername" error-field="legacyDbUsername"/>
                                <x-input :label="__('Password')" type="password" wire:model.blur="legacyDbPassword" error-field="legacyDbPassword" class="md:col-span-2"/>
                            </div>
                        @endif

                        <x-input :label="__('Legacy storage path')" wire:model.blur="legacyStoragePath" error-field="legacyStoragePath"
                                 hint="{{ __('Absolute path to the old instance storage directory.') }}"/>
                        <x-select :label="__('Uploads without an owner')" :options="$this->orphanStrategies" wire:model="legacyOrphans" error-field="legacyOrphans"/>
                        <x-checkbox :label="__('Generate previews after import (requires a queue worker)')" wire:model="legacyWithPreviews"/>

                        <div class="flex items-center gap-3">
                            <x-button :label="__('Preview')" icon="o-magnifying-glass" class="btn-soft" wire:click="previewLegacy" spinner="previewLegacy"/>
                            @if($legacyPreview)
                                <x-badge :value="__(':users users, :uploads uploads', ['users' => $legacyPreview['users'], 'uploads' => $legacyPreview['uploads']])" class="badge-info"/>
                            @endif
                        </div>
                    </div>
                @else
                    <p class="text-sm opacity-70 mt-2">{{ __('Skip this step if this is a fresh installation.') }}</p>
                @endif
            </x-step>
        </x-steps>

        <div class="flex items-center justify-between mt-8">
            <div>
                @if($step > 1)
                    <x-button :label="__('Back')" icon="o-arrow-left" wire:click="previousStep"/>
                @endif
            </div>
            <div>
                @if($step < 5)
                    <x-button :label="__('Next')" icon-right="o-arrow-right" class="btn-primary" wire:click="nextStep" spinner="nextStep"/>
                @else
                    <x-button :label="__('Install XBackBone')" icon="o-rocket-launch" class="btn-primary" wire:click="install" spinner="install"/>
                @endif
            </div>
        </div>
    </x-card>
</div>
