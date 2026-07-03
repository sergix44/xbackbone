<?php

namespace XBB\Livewire\Auth;

use Livewire\Component;
use Mary\Traits\Toast;

class ForgotPassword extends Component
{
    use Toast;

    public function render()
    {
        return view('livewire.auth.forgot-password')
            ->layout('layouts::auth')
            ->title('Forgot Password');
    }
}
