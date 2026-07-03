<?php

namespace XBB\Livewire\Auth;

use Livewire\Component;

class ResetPassword extends Component
{
    public function render()
    {
        return view('livewire.auth.reset-password')
            ->layout('layouts::auth')
            ->title('Reset Password');
    }
}
