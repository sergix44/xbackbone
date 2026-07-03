<?php

namespace XBB\Livewire\User;

use XBB\Actions\Fortify\UpdateUserPassword;
use XBB\Actions\Fortify\UpdateUserProfileInformation;
use XBB\Actions\Token\RevokeToken;
use XBB\Actions\User\DeleteUserAccount;
use XBB\Models\Properties\ResourceType;
use XBB\Models\User;
use XBB\Support\Helpers;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Laravel\Passkeys\Actions\DeletePasskey;
use Laravel\Passkeys\Passkey;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
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

    /* DELETE ACCOUNT */
    public bool $confirmingDelete = false;

    public ?string $deletePassword = null;

    public function mount(string $tab = 'profile'): void
    {
        $this->tab = $tab;
        $this->user = auth()->user();

        if ($tab === 'profile') {
            $this->name = $this->user->name;
            $this->email = $this->user->email;
            $this->theme = Helpers::theme($this->user);

            $this->themes = collect(config('themes'))
                ->map(fn ($theme, $key) => (object) ['id' => $theme, 'name' => $theme])
                ->sortBy('name')
                ->prepend((object) ['id' => null, 'name' => '(default)'])
                ->toArray();
        }
    }

    public function updateTheme(): void
    {
        $user = auth()->user();
        // Store null (never an empty string) when the user inherits the
        // global default, so the layout's fallback is never shadowed.
        $user->theme = $this->theme ?: null;
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

        $this->currentPassword = null;
        $this->newPassword = null;
        $this->user = $this->user->refresh();

        $this->success(__('Profile updated successfully!'));
    }

    public function resendVerification(): void
    {
        auth()->user()->sendEmailVerificationNotification();

        $this->success(__('A new verification link has been sent to your email address.'));
    }

    public function revokeSelectedTokens(RevokeToken $revokeToken): void
    {
        if (empty($this->selectedTokens)) {
            $this->warning(__('No tokens selected.'));

            return;
        }

        $this->user->tokens
            ->whereIn('id', $this->selectedTokens)
            ->each(fn ($token) => $revokeToken($token, $this->user));

        $this->user = $this->user->refresh();
        $this->selectedTokens = [];
        $this->success(__('Selected tokens revoked successfully!'));
    }

    /**
     * The passkeys registered by the user, newest first.
     *
     * @return Collection<int, Passkey>
     */
    #[Computed]
    public function passkeys(): Collection
    {
        return $this->user->passkeys()->latest()->get();
    }

    /**
     * Delete one of the current user's passkeys. Scoping the query through the
     * relation guarantees a user can only ever delete their own passkey.
     */
    public function deletePasskey(int $passkeyId, DeletePasskey $deletePasskey): void
    {
        $passkey = $this->user->passkeys()->whereKey($passkeyId)->first();

        if ($passkey) {
            $deletePasskey($this->user, $passkey);
        }

        unset($this->passkeys);
        $this->success(__('Passkey removed successfully!'));
    }

    /**
     * Refresh the passkey list after the browser completes a registration
     * ceremony (dispatched from the passkeyManager Alpine component).
     */
    #[On('passkey-registered')]
    public function onPasskeyRegistered(): void
    {
        unset($this->passkeys);
        $this->success(__('Passkey added successfully!'));
    }

    /**
     * Aggregate, display-ready statistics for the resources the user has uploaded.
     *
     * @return array{media: string, size: string, views: string, downloads: string, quota: ?string, quota_unlimited: bool, quota_percent: ?int}
     */
    #[Computed]
    public function stats(): array
    {
        $aggregate = $this->user->resources()
            ->where('type', '!=', ResourceType::DIRECTORY->value)
            ->selectRaw('COUNT(*) as media, COALESCE(SUM(size), 0) as size, COALESCE(SUM(views), 0) as views, COALESCE(SUM(downloads), 0) as downloads')
            ->toBase()
            ->first();

        $used = (int) $aggregate->size;
        $unlimited = $this->user->hasUnlimitedQuota();
        $quota = (int) $this->user->quota;

        return [
            'media' => number_format((int) $aggregate->media),
            'size' => Helpers::humanizeBytes($used),
            'views' => number_format((int) $aggregate->views),
            'downloads' => number_format((int) $aggregate->downloads),
            'quota' => $unlimited ? null : Helpers::humanizeBytes($quota),
            'quota_unlimited' => $unlimited,
            'quota_percent' => $unlimited ? null : ($quota > 0 ? min(100, (int) round($used / $quota * 100)) : 100),
        ];
    }

    public function deleteAccount(DeleteUserAccount $deleteUserAccount): mixed
    {
        $this->validate(
            ['deletePassword' => ['required', 'current_password:web']],
            ['deletePassword.current_password' => __('The provided password does not match your current password.')],
            ['deletePassword' => __('password')],
        );

        $user = auth()->user();

        // Log out before deleting: logging out cycles the remember token and saves
        // the model, which would re-insert the row if the user were already gone.
        Auth::logout();
        Session::invalidate();
        Session::regenerateToken();

        $deleteUserAccount($user, $user);

        return $this->redirect(route('login'));
    }

    public function render(): object
    {
        return view('livewire.user.profile')->title('Profile');
    }
}
