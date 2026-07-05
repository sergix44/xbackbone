<div class="grid grid-cols-12 gap-6">
    <div class="md:col-span-3 col-span-12">
        <x-menu class="rounded-lg bg-base-100" activate-by-route>
            <x-menu-item title="General Settings" icon="o-cog-6-tooth" :link="route('admin.settings')" exact/>
            <x-menu-item title="User Management" icon="o-users" :link="route('admin.settings', ['tab' => 'users'])"/>
            <x-menu-item title="Statistics" icon="o-chart-bar" :link="route('admin.settings', ['tab' => 'statistics'])"/>
            <x-menu-item title="Activity Log" icon="o-clipboard-document-list" :link="route('admin.settings', ['tab' => 'activity'])"/>
            <x-menu-item title="Updates" icon="o-arrow-up-circle" :link="route('admin.settings', ['tab' => 'updates'])"/>
        </x-menu>
    </div>
    <div class="md:col-span-9 col-span-12 flex flex-col gap-2">
        @if($tab === 'users')
            <livewire:admin.user-management/>
        @elseif($tab === 'updates')
            <livewire:admin.updates/>
        @elseif($tab === 'activity')
            <livewire:activity-log/>
        @elseif($tab === 'statistics')
            @php
                $breakdownHeaders = [
                    ['key' => 'type', 'label' => 'Type', 'sortable' => false],
                    ['key' => 'count', 'label' => 'Count', 'sortable' => false, 'class' => 'text-right'],
                    ['key' => 'size', 'label' => 'Size', 'sortable' => false, 'class' => 'text-right'],
                ];
                $uploaderHeaders = [
                    ['key' => 'name', 'label' => 'User', 'sortable' => false],
                    ['key' => 'media', 'label' => 'Media', 'sortable' => false, 'class' => 'text-right'],
                    ['key' => 'size', 'label' => 'Storage', 'sortable' => false, 'class' => 'text-right'],
                ];
            @endphp

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2">
                <x-stat title="{{ __('Users') }}" :value="$this->stats['users']" icon="o-users" color="text-secondary"/>
                <x-stat title="{{ __('Media') }}" :value="$this->stats['media']" icon="o-photo" color="text-success"/>
                <x-stat title="{{ __('Storage') }}" :value="$this->stats['size']" icon="o-circle-stack" color="text-warning"/>
                <x-stat title="{{ __('Views') }}" :value="$this->stats['views']" icon="o-eye" color="text-info"/>
                <x-stat title="{{ __('Downloads') }}" :value="$this->stats['downloads']" icon="o-arrow-down-tray" color="text-primary"/>
            </div>

            <div class="card bg-base-100">
                <div class="card-body">
                    <h1 class="card-title">{{ __('Media by Type') }}</h1>
                    <x-table :headers="$breakdownHeaders" :rows="$this->typeBreakdown">
                        @scope('cell_type', $row)
                            <span class="inline-flex items-center gap-2">
                                <x-icon :name="$row['icon']" class="w-5 h-5 {{ $row['color'] }}"/>
                                {{ $row['label'] }}
                            </span>
                        @endscope
                        <x-slot:empty>
                            <x-alert title="{{ __('No media uploaded yet.') }}" icon="o-information-circle"/>
                        </x-slot:empty>
                    </x-table>
                </div>
            </div>

            <div class="card bg-base-100">
                <div class="card-body">
                    <h1 class="card-title">{{ __('Top Uploaders') }}</h1>
                    <x-table :headers="$uploaderHeaders" :rows="$this->topUploaders">
                        <x-slot:empty>
                            <x-alert title="{{ __('No media uploaded yet.') }}" icon="o-information-circle"/>
                        </x-slot:empty>
                    </x-table>
                </div>
            </div>
        @else
            <div class="card bg-base-100">
                <div class="card-body">
                    <h1 class="card-title">{{ __('General Settings') }}</h1>

                    @if(config('queue.default') === 'sync')
                        <x-alert class="alert-warning" icon="o-exclamation-triangle"
                                 :title="__('Queue running synchronously')"
                                 :description="__('Previews are generated during the upload request, which slows uploads down and may fail for large files or videos. Configure a queue worker and set QUEUE_CONNECTION=database to process them in the background.')"/>
                    @endif

                    <div class="divider">{{ __('Registration') }}</div>
                    <x-toggle :label="__('Enable user sign up')"
                              :hint="__('Allow new visitors to create an account.')"
                              wire:model="signupEnabled"
                              wire:change="updateSignup()"/>

                    <div class="divider mt-8">{{ __('Appearance') }}</div>
                    <x-select :label="__('Default theme')"
                              :hint="__('Theme applied to guests and to users who have not chosen their own.')"
                              icon="o-paint-brush"
                              :options="$themes"
                              :value="$defaultTheme"
                              wire:model="defaultTheme"
                              wire:change="updateDefaultTheme()"
                              inline/>

                    <div class="divider mt-8">{{ __('API Documentation') }}</div>
                    <x-toggle :label="__('Make API documentation public')"
                              :hint="__('Allow anyone to view the API docs. When disabled, only logged in users can access them.')"
                              wire:model="apiDocsPublic"
                              wire:change="updateApiDocsPublic()"/>
                </div>
            </div>
        @endif
    </div>
</div>
