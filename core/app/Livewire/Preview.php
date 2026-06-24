<?php

namespace App\Livewire;

use App\Actions\Resource\RecordResourceView;
use App\Models\Resource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Mary\Traits\Toast;

class Preview extends Component
{
    use Toast;

    /**
     * Largest text file rendered inline; larger files only offer a download.
     */
    private const MAX_TEXT_PREVIEW_BYTES = 1024 * 1024;

    /**
     * Wrong password attempts allowed per {@see UNLOCK_DECAY_SECONDS} window.
     */
    private const MAX_UNLOCK_ATTEMPTS = 5;

    private const UNLOCK_DECAY_SECONDS = 60;

    public Resource $resource;

    /**
     * Whether the resource is password-protected and the current visitor has not
     * unlocked it yet. While locked, the view renders only the password form.
     */
    public bool $locked = false;

    public ?string $passwordInput = null;

    public function mount(Resource $resource, RecordResourceView $recordResourceView, ?string $ext = null): void
    {
        view()->share('previewMode', true);
        $this->resource = $resource;

        // Accessibility (private/expired) and the password gate are enforced by the
        // EnsureResourceAccessible middleware before this point; here we only need
        // to know whether to render the unlock form or the content.
        if ($ext && $resource->extension !== $ext) {
            abort(404);
        }

        $this->locked = $resource->isLockedFor(auth()->user(), session()->driver());

        // Only a visitor who actually sees the content counts as a view; a locked
        // page is counted later, once unlocked.
        if (! $this->locked) {
            $recordResourceView($resource);
        }
    }

    /**
     * Verify the supplied password and, on success, unlock the resource for the
     * rest of the browser session. Attempts are rate limited to deter brute force.
     * The page is then reloaded so the freshly-unlocked content and the layout's
     * navbar actions render together; {@see mount()} records the view on reload.
     */
    public function unlock(): void
    {
        if (! $this->locked) {
            return;
        }

        $key = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($key, self::MAX_UNLOCK_ATTEMPTS)) {
            $this->addError('passwordInput', __('Too many attempts. Please try again in :seconds seconds.', [
                'seconds' => RateLimiter::availableIn($key),
            ]));

            return;
        }

        $this->validate(['passwordInput' => ['required', 'string']]);

        if (! Hash::check((string) $this->passwordInput, (string) $this->resource->password)) {
            RateLimiter::hit($key, self::UNLOCK_DECAY_SECONDS);
            $this->addError('passwordInput', __('The provided password is incorrect.'));

            return;
        }

        RateLimiter::clear($key);
        session()->put($this->resource->unlockSessionKey(), true);

        $this->redirect(route('preview', ['resource' => $this->resource->code]), navigate: true);
    }

    /**
     * Whether the resource is too large to render its text inline; such files
     * only offer a download. Displayability is already gated by the view via
     * {@see ResourceType::isDisplayable()}.
     */
    #[Computed]
    public function textTooLarge(): bool
    {
        return ($this->resource->size ?? 0) > self::MAX_TEXT_PREVIEW_BYTES;
    }

    /**
     * The textual content of the resource. Pastes live in the {@see Resource::$data}
     * column, while uploaded text files are read from storage.
     */
    #[Computed]
    public function textContent(): string
    {
        if ($this->resource->has_inline_content) {
            return $this->resource->data ?? '';
        }

        return Storage::get($this->resource->storage_path) ?? '';
    }

    public function render()
    {
        return view('livewire.preview')->title($this->resource->filename ?? $this->resource->code);
    }

    private function throttleKey(): string
    {
        return 'resource-unlock:'.$this->resource->id.':'.request()->ip();
    }
}
