<?php

namespace XBB\Livewire\Auth;

use Livewire\Component;

class VerifyEmail extends Component
{
    public function render()
    {
        return view('livewire.auth.verify-email')
            ->layout('layouts::auth')
            ->title('Verify Email Address');
    }
}
