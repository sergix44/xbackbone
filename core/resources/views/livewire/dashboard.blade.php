<div>
    <x-upload-drawer/>
    <div class="flex flex-col lg:flex-row items-center w-full mx-auto gap-4 lg:gap-0">
        <div class="flex items-center justify-center lg:justify-start gap-2 w-full lg:w-1/2">
            <x-button label="New" class="btn-primary" icon="o-plus" wire:click="$toggle('showUploadDrawer')"/>
            <x-input placeholder="Search..." inline class="flex-1">
                <x-slot:append>
                    <x-button icon="o-magnifying-glass" class="join-item btn-accent"/>
                </x-slot:append>
            </x-input>
        </div>
        <div class="flex justify-center lg:shrink-0 lg:my-0">
            {{ $this->resources->links() }}
        </div>
        <div class="flex items-center justify-center lg:justify-end gap-2 lg:w-1/2">
            <div class="join">
                <x-dropdown label="Sort by" class="btn-accent rounded-r-none join-item">
                    <x-menu-item title="It should align correctly on right side"/>
                    <x-menu-item title="Yes!"/>
                </x-dropdown>
                <x-button icon="o-bars-3-bottom-right" class="btn-accent join-item"/>
            </div>
        </div>
    </div>
    <div class="mt-5 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 2xl:grid-cols-5 gap-4">
        @foreach($this->resources as $resource)
            <x-resource :resource="$resource" wire:key="resource-{{ $resource->id }}"/>
        @endforeach
    </div>
    <div class="flex justify-center mt-4">
        {{ $this->resources->links() }}
    </div>

    <x-modal wire:model="confirmingDelete" title="Delete resource"
             subtitle="This action cannot be undone." separator>
        <p>Are you sure you want to delete this resource?</p>
        <x-slot:actions>
            <x-button label="Cancel" @click="$wire.confirmingDelete = false"/>
            <x-button label="Delete" class="btn-error" wire:click="deleteResource" spinner="deleteResource"/>
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="showSettingsModal" title="Resource settings"
             subtitle="Manage password protection and expiration." separator>
        <div class="flex flex-col gap-4">
            <x-datetime label="Expires at" wire:model="settingsExpiresAt" icon="o-clock"
                        hint="Leave empty for no expiration. Once passed, the resource becomes private."/>

            @unless($settingsRemovePassword)
                <x-password label="Password" wire:model="settingsPassword"
                            :hint="$settingsHasPassword ? 'Leave blank to keep the current password.' : 'Set a password to require it before viewing or downloading.'"/>
            @endunless

            @if($settingsHasPassword)
                <x-checkbox label="Remove password protection" wire:model.live="settingsRemovePassword"/>
            @endif
        </div>

        <x-slot:actions>
            <x-button label="Cancel" @click="$wire.showSettingsModal = false"/>
            <x-button label="Save" icon="o-check-circle" class="btn-primary" wire:click="saveSettings" spinner="saveSettings"/>
        </x-slot:actions>
    </x-modal>
</div>

@script
<script>
    document.querySelector('#main').addEventListener('dragover', e => {
        e.preventDefault();
        $wire.showUploadDrawer = true;
    });

    Livewire.on('clipboard:copied', ({text}) => {
        $wire.$call('success', 'Copied to clipboard', text);
    });
</script>
@endscript
