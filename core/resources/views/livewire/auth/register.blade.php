<div>
    <x-form wire:submit="register" no-separator>
        <x-input placeholder="Username" label="Username" type="text" wire:model="name" inline/>
        <x-input placeholder="E-mail" label="E-mail" type="email" wire:model="email" inline/>
        <x-input placeholder="Password" label="Password" type="password" wire:model="password" inline/>
        <x-input placeholder="Confirm password" label="Confirm password" type="password" wire:model="password_confirmation" inline/>

        <div class="flex flex-col gap-2 mt-6">
            <x-button label="Register" class="btn-primary btn-block" type="submit" spinner="register"/>
            <x-button label="Already have an account?" class="btn-link btn-sm" link="{{ route('login') }}"/>
        </div>
    </x-form>
</div>
