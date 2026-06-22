<?php

namespace App\Livewire\Auth;

use Illuminate\Auth\Events\Registered;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Fortify\Fortify;
use Laravel\Pennant\Feature;
use Livewire\Component;

class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        abort_unless(Feature::active('signup'), 404);
    }

    public function register(CreatesNewUsers $creator)
    {
        $user = $creator->create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
        ]);

        event(new Registered($user));

        auth()->login($user);

        return redirect()->intended(Fortify::redirects('register'));
    }

    public function render()
    {
        return view('livewire.auth.register')
            ->layout('layouts::auth')
            ->title('Register');
    }
}
