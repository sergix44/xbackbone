<?php

namespace App\Support;

use App\Models\Resource;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class ActivityEvent
{
    /**
     * Presentation metadata for every activity-log event the app records, keyed by
     * the dot-notation description passed to {@see Activity()}->log(). Colours are
     * daisyUI semantic tokens resolved to concrete classes in the Blade view.
     *
     * @var array<string, array{label: string, icon: string, color: string}>
     */
    private const EVENTS = [
        'resource.uploaded' => ['label' => 'Uploaded a file', 'icon' => 'o-arrow-up-tray', 'color' => 'success'],
        'resource.published' => ['label' => 'Made a file public', 'icon' => 'o-eye', 'color' => 'info'],
        'resource.hidden' => ['label' => 'Made a file private', 'icon' => 'o-eye-slash', 'color' => 'warning'],
        'resource.updated' => ['label' => 'Updated file settings', 'icon' => 'o-cog-6-tooth', 'color' => 'info'],
        'resource.deleted' => ['label' => 'Deleted a file', 'icon' => 'o-trash', 'color' => 'error'],
        'user.created' => ['label' => 'Created a user', 'icon' => 'o-user-plus', 'color' => 'success'],
        'user.updated' => ['label' => 'Updated a user', 'icon' => 'o-user', 'color' => 'info'],
        'user.deleted' => ['label' => 'Deleted a user', 'icon' => 'o-user-minus', 'color' => 'error'],
        'user.password_changed' => ['label' => 'Changed the password', 'icon' => 'o-key', 'color' => 'warning'],
        'user.profile_updated' => ['label' => 'Updated the profile', 'icon' => 'o-identification', 'color' => 'info'],
        'token.created' => ['label' => 'Created an API token', 'icon' => 'o-command-line', 'color' => 'success'],
        'token.revoked' => ['label' => 'Revoked an API token', 'icon' => 'o-command-line', 'color' => 'error'],
        'passkey.added' => ['label' => 'Added a passkey', 'icon' => 'o-finger-print', 'color' => 'success'],
        'passkey.removed' => ['label' => 'Removed a passkey', 'icon' => 'o-finger-print', 'color' => 'error'],
        'auth.login' => ['label' => 'Signed in', 'icon' => 'o-arrow-right-end-on-rectangle', 'color' => 'success'],
        'auth.logout' => ['label' => 'Signed out', 'icon' => 'o-arrow-left-start-on-rectangle', 'color' => 'neutral'],
        'auth.registered' => ['label' => 'Registered an account', 'icon' => 'o-user-plus', 'color' => 'success'],
        'auth.lockout' => ['label' => 'Locked out after too many attempts', 'icon' => 'o-lock-closed', 'color' => 'warning'],
        'auth.failed' => ['label' => 'Failed sign-in attempt', 'icon' => 'o-exclamation-triangle', 'color' => 'error'],
    ];

    /**
     * Label, icon and colour for a given event description. Unknown descriptions
     * fall back to a humanised label so future events still render sensibly.
     *
     * @return array{label: string, icon: string, color: string}
     */
    public static function describe(string $description): array
    {
        return self::EVENTS[$description] ?? [
            'label' => Str::headline(Str::after($description, '.') ?: $description),
            'icon' => 'o-bolt',
            'color' => 'neutral',
        ];
    }

    /**
     * The categories events are grouped into (the part before the dot), used to
     * populate the feed's filter.
     *
     * @return array<string, string>
     */
    public static function categories(): array
    {
        return [
            'resource' => __('Files'),
            'user' => __('Users'),
            'token' => __('API tokens'),
            'passkey' => __('Passkeys'),
            'auth' => __('Authentication'),
        ];
    }

    /**
     * A human label for what the activity acted upon, or null when the subject is
     * gone (e.g. a deleted file) or the event has no subject (e.g. a login).
     */
    public static function subjectLabel(Activity $activity): ?string
    {
        $subject = $activity->subject;

        return match (true) {
            $subject instanceof Resource => $subject->name ?? $subject->filename ?? $subject->code,
            $subject instanceof User => $subject->name,
            $subject instanceof Model => $subject->getAttribute('name'),
            default => null,
        };
    }

    /**
     * A link to the subject when it still exists and is viewable, otherwise null.
     */
    public static function subjectUrl(Activity $activity): ?string
    {
        $subject = $activity->subject;

        return $subject instanceof Resource
            ? route('preview', ['resource' => $subject->code])
            : null;
    }
}
