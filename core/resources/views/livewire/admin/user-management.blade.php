<div>
    @php
        $headers = [
            ['key' => 'name', 'label' => __('User')],
            ['key' => 'email', 'label' => __('Email')],
            ['key' => 'is_admin', 'label' => __('Role'), 'sortable' => false],
            ['key' => 'status', 'label' => __('Status'), 'sortable' => false],
            ['key' => 'quota', 'label' => __('Quota'), 'sortable' => false, 'class' => 'text-right'],
            ['key' => 'media_count', 'label' => __('Media'), 'sortable' => false, 'class' => 'text-right'],
            ['key' => 'created_at', 'label' => __('Joined')],
            ['key' => 'actions', 'label' => '', 'sortable' => false, 'class' => 'text-right w-px'],
        ];
    @endphp

    <div class="card bg-base-100">
        <div class="card-body">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <h1 class="card-title flex-1">{{ __('User Management') }}</h1>
                <x-input placeholder="{{ __('Search...') }}" wire:model.live.debounce.300ms="search" icon="o-magnifying-glass" clearable class="w-full sm:w-64"/>
                <x-button :label="__('New user')" icon="o-plus" class="btn-primary" wire:click="openCreate"/>
            </div>

            <x-table :headers="$headers" :rows="$this->users" :sort-by="$sortBy" class="mt-2">
                @scope('cell_name', $user)
                    <div class="flex items-center gap-3">
                        <x-avatar :image="$user->avatar" class="!w-8"/>
                        <span class="font-medium">{{ $user->name }}</span>
                    </div>
                @endscope

                @scope('cell_is_admin', $user)
                    @if($user->is_admin)
                        <x-badge :value="__('Admin')" class="badge-primary"/>
                    @else
                        <x-badge :value="__('User')" class="badge-ghost"/>
                    @endif
                @endscope

                @scope('cell_status', $user)
                    @php
                        $statusClass = match($user->status) {
                            \App\Models\Properties\UserStatus::ENABLED => 'badge-success',
                            \App\Models\Properties\UserStatus::DISABLED => 'badge-error',
                            \App\Models\Properties\UserStatus::API_ONLY => 'badge-info',
                            \App\Models\Properties\UserStatus::SSO_ONLY => 'badge-warning',
                        };
                    @endphp
                    <x-badge :value="$user->status->label()" class="{{ $statusClass }} badge-soft"/>
                @endscope

                @scope('cell_quota', $user)
                    {{ $user->quota < 0 ? __('Unlimited') : \App\Support\Helpers::humanizeBytes((int) $user->quota) }}
                @endscope

                @scope('cell_media_count', $user)
                    {{ number_format((int) $user->media_count) }}
                @endscope

                @scope('cell_created_at', $user)
                    {{ $user->created_at?->diffForHumans() }}
                @endscope

                @scope('cell_actions', $user)
                    <div class="flex justify-end gap-1">
                        <x-button icon="o-pencil-square" class="btn-ghost btn-sm" wire:click="openEdit({{ $user->id }})" :tooltip="__('Edit')"/>
                        <x-button icon="o-trash" class="btn-ghost btn-sm text-error" wire:click="confirmDelete({{ $user->id }})" :tooltip="__('Delete')"/>
                    </div>
                @endscope

                <x-slot:empty>
                    <x-alert title="{{ __('No users found.') }}" icon="o-information-circle"/>
                </x-slot:empty>
            </x-table>

            <div class="mt-4">
                {{ $this->users->links() }}
            </div>
        </div>
    </div>

    {{-- Create / edit modal --}}
    <x-modal wire:model="showUserModal" :title="$editingId ? __('Edit user') : __('Create user')" separator>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-input :label="__('Name')" wire:model="name" error-field="name" inline/>
            <x-input :label="__('Email')" type="email" wire:model="email" error-field="email" inline/>
            <x-input :label="__('Password')" type="password" wire:model="password" error-field="password"
                     :hint="$editingId ? __('Leave blank to keep the current password.') : null" inline/>
            <x-select :label="__('Status')" :options="$this->statusOptions" wire:model="status" error-field="status" inline/>
            <x-toggle :label="__('Administrator')" wire:model="isAdmin" class="md:col-span-2"/>
            <x-checkbox :label="__('Unlimited quota')" wire:model.live="unlimitedQuota" class="md:col-span-2"/>
            @unless($unlimitedQuota)
                <x-input :label="__('Quota (MB)')" type="number" min="0" wire:model="quotaMb" error-field="quotaMb" class="md:col-span-2" inline/>
            @endunless
        </div>

        <x-slot:actions>
            <x-button :label="__('Cancel')" @click="$wire.showUserModal = false"/>
            <x-button :label="__('Save')" icon="o-check-circle" class="btn-primary" wire:click="saveUser" spinner="saveUser"/>
        </x-slot:actions>
    </x-modal>

    {{-- Delete confirmation modal --}}
    <x-modal wire:model="confirmingDelete" :title="__('Delete user')" :subtitle="__('This action cannot be undone.')" separator>
        <p>{{ __('Are you sure you want to delete this user? All their uploaded files and API tokens will be permanently removed.') }}</p>
        <x-slot:actions>
            <x-button :label="__('Cancel')" @click="$wire.confirmingDelete = false"/>
            <x-button :label="__('Delete user')" class="btn-error" icon="o-trash" wire:click="deleteUser" spinner="deleteUser"/>
        </x-slot:actions>
    </x-modal>
</div>
