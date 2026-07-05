<?php

namespace XBB\Actions\Import;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;
use XBB\Actions\Admin\CreateUser;
use XBB\Models\Properties\UserStatus;
use XBB\Models\User;

class ImportLegacyUser
{
    /**
     * Import a single legacy `users` row into a {@see User}.
     *
     * Matching is by email so a re-run never clobbers credentials the operator may
     * have changed: an existing user is returned untouched. Admin-only fields bypass
     * {@see User::$fillable} and are assigned directly, mirroring
     * {@see CreateUser}.
     *
     * @param  object  $row  A legacy `users` row (id, email, username, password, active, is_admin, registration_date, max_disk_quota).
     * @return array{user: User|null, action: 'created'|'skipped'|'would-create'}
     */
    public function __invoke(object $row, bool $dryRun = false): array
    {
        $email = Str::lower(trim((string) $row->email));

        $existing = User::query()->where('email', $email)->first();

        if ($existing !== null) {
            return ['user' => $existing, 'action' => 'skipped'];
        }

        if ($dryRun) {
            return ['user' => null, 'action' => 'would-create'];
        }

        $registeredAt = $this->parseTimestamp($row->registration_date ?? null);
        $legacyPassword = (string) ($row->password ?? '');

        $user = new User;
        $user->name = $this->resolveName($row, $email);
        $user->email = $email;
        // A throwaway password keeps the NOT NULL column valid; the real legacy hash is
        // written afterwards, bypassing the model cast (see storeLegacyPassword()).
        $user->password = Str::password(32);
        $user->is_admin = (bool) ($row->is_admin ?? false);
        $user->status = ((int) ($row->active ?? 1)) === 1 ? UserStatus::ENABLED : UserStatus::DISABLED;
        $user->quota = (int) ($row->max_disk_quota ?? -1);
        $user->email_verified_at = $registeredAt;
        // Preserve the original signup time. Setting created_at explicitly marks it dirty,
        // so Eloquent's auto-timestamps leave it untouched on insert.
        $user->created_at = $registeredAt;
        $user->save();

        // LDAP-only accounts have no usable local hash; the random password above then
        // stands in, leaving the account without a guessable secret.
        if ($legacyPassword !== '' && Hash::isHashed($legacyPassword)) {
            $this->storeLegacyPassword($user, $legacyPassword);
        }

        return ['user' => $user, 'action' => 'created'];
    }

    private function resolveName(object $row, string $email): string
    {
        $username = trim((string) ($row->username ?? ''));

        return $username !== '' ? $username : Str::before($email, '@');
    }

    /**
     * Persist the legacy password hash verbatim, bypassing the model's `hashed` cast which
     * rejects a hash produced by an algorithm other than the application's current default.
     * Laravel re-hashes it to the current algorithm on the user's first successful login
     * (provided HASH_VERIFY is disabled so the legacy algorithm is accepted).
     */
    private function storeLegacyPassword(User $user, string $hash): void
    {
        User::query()->whereKey($user->getKey())->update(['password' => $hash]);

        $attributes = $user->getAttributes();
        $attributes['password'] = $hash;
        $user->setRawAttributes($attributes, sync: true);
    }

    private function parseTimestamp(?string $value): Carbon
    {
        if ($value === null || $value === '' || str_starts_with($value, '0000-00-00')) {
            return now();
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return now();
        }
    }
}
