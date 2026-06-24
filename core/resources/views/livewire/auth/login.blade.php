<div>
    <x-form wire:submit="authenticate" no-separator>
        <x-input placeholder="Username" label="Username" type="email" wire:model="form.email" inline />
        <x-input placeholder="Password" label="Password" type="password" wire:model="form.password" inline />
        <x-checkbox label="Remember me" wire:model="form.remember"/>

        <div class="flex flex-col gap-2 mt-6">
            <x-button label="Login" class="btn-primary btn-block" type="submit" spinner="authenticate"/>
            @feature('signup')
                <x-button label="Register" class="btn-block" link="{{ route('register') }}"/>
            @endfeature
            <x-button label="Forgot Password?" class="btn-link btn-sm" link="{{ route('password.request') }}"/>
        </div>
    </x-form>

    <div x-data="passkeyLogin()" x-show="supported" x-cloak class="mt-4">
        <div class="divider text-xs opacity-60">{{ __('or') }}</div>
        <x-button :label="__('Sign in with a passkey')" icon="o-finger-print" class="btn-block"
                  @click="login()" ::disabled="busy"/>
        <p x-show="error" x-text="error" x-cloak class="text-error text-sm text-center mt-2"></p>
    </div>
</div>
