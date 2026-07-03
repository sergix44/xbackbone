<?php

namespace XBB\Livewire\Auth;

use XBB\Livewire\Forms\LoginForm;
use Laravel\Fortify\Fortify;
use Livewire\Component;
use Mary\Traits\Toast;

class Login extends Component
{
    use Toast;

    public LoginForm $form;

    public function authenticate()
    {
        $this->form->validate();
        $this->form->authenticate();

        return redirect()->intended(Fortify::redirects('dashboard'));
    }

    public function render()
    {
        return view('livewire.auth.login')
            ->layout('layouts::auth')
            ->title('Login');
    }
}
