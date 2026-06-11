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
            export
        @elseif($tab === 'delete')
            delete
        @else
            <div class="card bg-base-100">
                <div class="card-body">
                    <x-avatar :image="$user->avatar" class="!w-22">
                        <x-slot:title class="text-3xl !font-bold pl-2">
                            {{ $name }}
                        </x-slot:title>

                        <x-slot:subtitle class="grid gap-1 mt-2 pl-2 text-xs">
                            <x-icon name="o-paper-airplane" label="12 posts"/>
                            <x-icon name="o-chat-bubble-left" label="45 comments"/>
                        </x-slot:subtitle>
                    </x-avatar>
                    <div class="divider mt-8">Profile</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input placeholder="Username" label="Username" type="text" wire:model="name" inline/>
                        <x-input placeholder="E-mail" label="E-mail" type="email" wire:model="email" inline/>

                        <x-input placeholder="Current password" label="Current password" type="password" wire:model="currentPassword" inline/>
                        <x-input placeholder="New password" label="New password" type="password" wire:model="newPassword" inline/>

                        <div>
                            <x-button label="Save" icon="o-check-circle" class="btn-primary" wire:click="updateProfile()" spinner/>
                        </div>
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
