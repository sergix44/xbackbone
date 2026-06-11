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
    <div class="mt-5 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($this->resources as $resource)
            <x-resource :resource="$resource"/>
        @endforeach
    </div>
    <div class="flex justify-center mt-4">
        {{ $this->resources->links() }}
    </div>
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
