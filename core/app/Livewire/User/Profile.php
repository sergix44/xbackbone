<?php

namespace App\Livewire\User;

use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Laravel\Pennant\Feature;
use Livewire\Component;
use Mary\Traits\Toast;

class Profile extends Component
{
    use Toast;

    protected $listeners = [
        'reload' => '$refresh',
    ];

    public string $tab;

    public User $user;

    /* PROFILE */
    public string $name = '';

    public string $email = '';

    public ?string $currentPassword = null;

    public ?string $newPassword = null;

    public ?string $theme = null;

    public array $themes = [];

    /* TOKENS */
    public array $selectedTokens = [];

    public function mount(string $tab = 'profile'): void
    {
        $this->tab = $tab;
        $this->user = auth()->user();

        if ($tab === 'profile') {
            $this->name = $this->user->name;
            $this->email = $this->user->email;
            $this->theme = $this->user->theme ?? Feature::value('default-theme');

            $this->themes = collect(config('themes'))
                ->map(fn ($theme, $key) => (object) ['id' => $theme, 'name' => $theme])
                ->sortBy('name')
                ->prepend(['id' => null, 'name' => '(default)'])
                ->toArray();
        }
    }

    public function updateTheme(): void
    {
        $user = auth()->user();
        $user->theme = $this->theme;
        $user->save();

        $this->success(__('Theme updated successfully!'), redirectTo: '#');
    }

    public function updateProfile(): void
    {
        app(UpdateUserProfileInformation::class)->update(
            auth()->user(),
            ['name' => $this->name, 'email' => $this->email]
        );

        if ($this->currentPassword) {
            app(UpdateUserPassword::class)->update(
                auth()->user(),
                [
                    'current_password' => $this->currentPassword,
                    'password' => $this->newPassword,
                    'password_confirmation' => $this->newPassword,
                ]
            );
        }

        $this->success(__('Profile updated successfully!'));
    }

    public function revokeSelectedTokens(): void
    {
        if (empty($this->selectedTokens)) {
            $this->warning(__('No tokens selected.'));

            return;
        }

        $this->user->tokens
            ->whereIn('id', $this->selectedTokens)
            ->each(function ($token) {
                $token->delete();
            });

        $this->user = $this->user->refresh();
        $this->selectedTokens = [];
        $this->success(__('Selected tokens revoked successfully!'));
    }

    public function render(): object
    {
        return view('livewire.user.profile')->title('Profile');
    }
}
