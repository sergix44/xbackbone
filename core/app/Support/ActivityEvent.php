<?php

namespace XBB\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use XBB\Models\Resource;
use XBB\Models\User;

class ActivityEvent
{
    /**
     * Icon and colour for every activity-log event the app records, keyed by the
     * dot-notation description passed to {@see Activity()}->log(). Labels live in
     * lang/en/activity.php so they can be translated. Colours are daisyUI semantic
     * tokens resolved to concrete classes in the Blade view.
     *
     * @var array<string, array{icon: string, color: string}>
     */
    private const EVENTS = [
        'resource.uploaded' => ['icon' => 'o-arrow-up-tray', 'color' => 'success'],
        'resource.published' => ['icon' => 'o-eye', 'color' => 'info'],
        'resource.hidden' => ['icon' => 'o-eye-slash', 'color' => 'warning'],
        'resource.updated' => ['icon' => 'o-cog-6-tooth', 'color' => 'info'],
        'resource.deleted' => ['icon' => 'o-trash', 'color' => 'error'],
        'user.created' => ['icon' => 'o-user-plus', 'color' => 'success'],
        'user.updated' => ['icon' => 'o-user', 'color' => 'info'],
        'user.deleted' => ['icon' => 'o-user-minus', 'color' => 'error'],
        'user.password_changed' => ['icon' => 'o-key', 'color' => 'warning'],
        'user.profile_updated' => ['icon' => 'o-identification', 'color' => 'info'],
        'token.created' => ['icon' => 'o-command-line', 'color' => 'success'],
        'token.revoked' => ['icon' => 'o-command-line', 'color' => 'error'],
        'passkey.added' => ['icon' => 'o-finger-print', 'color' => 'success'],
        'passkey.removed' => ['icon' => 'o-finger-print', 'color' => 'error'],
        'auth.login' => ['icon' => 'o-arrow-right-end-on-rectangle', 'color' => 'success'],
        'auth.logout' => ['icon' => 'o-arrow-left-start-on-rectangle', 'color' => 'neutral'],
        'auth.registered' => ['icon' => 'o-user-plus', 'color' => 'success'],
        'auth.lockout' => ['icon' => 'o-lock-closed', 'color' => 'warning'],
        'auth.failed' => ['icon' => 'o-exclamation-triangle', 'color' => 'error'],
    ];

    /**
     * Label, icon and colour for a given event description. Unknown descriptions
     * fall back to a humanised label so future events still render sensibly.
     *
     * @return array{label: string, icon: string, color: string}
     */
    public static function describe(string $description): array
    {
        $meta = self::EVENTS[$description] ?? ['icon' => 'o-bolt', 'color' => 'neutral'];

        return [
            'label' => self::label($description),
            'icon' => $meta['icon'],
            'color' => $meta['color'],
        ];
    }

    /**
     * Translated label for a given event description, falling back to a
     * humanised version of the description when no translation exists.
     */
    private static function label(string $description): string
    {
        $key = "activity.{$description}";
        $translated = __($key);

        return is_string($translated) && $translated !== $key
            ? $translated
            : Str::headline(Str::after($description, '.') ?: $description);
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
