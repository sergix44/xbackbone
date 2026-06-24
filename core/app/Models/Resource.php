<?php

namespace App\Models;

use App\Models\Properties\ResourceType;
use App\Support\Helpers;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * @property ResourceType $type
 * @property ResourceType|null $preview_type
 * @property Carbon|null $published_at
 * @property Carbon|null $expires_at
 * @property string|null $password
 * @property-read User $user
 * @property-read bool $has_inline_content
 * @property-read string $raw_url
 * @property-read string $download_url
 * @property-read string $preview_url
 * @property-read string $preview_ext_url
 * @property-read string $thumbnail_url
 * @property-read string $deletion_url
 * @property-read string $storage_path
 * @property-read string $preview_path
 * @property-read bool $is_dir
 * @property-read string $display_name
 * @property-read string|null $size_human_readable
 * @property-read bool $has_preview
 * @property-read bool $preview_is_pending
 * @property-read bool $is_displayable
 * @property-read string $icon
 * @property-read string $icon_color
 */
class Resource extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'type',
        'user_id',
        'code',
        'legacy_code',
        'is_private',
        'data',
        'extension',
        'filename',
        'size',
        'mime',
        'preview_type',
        'preview_extension',
        'views',
        'downloads',
        'fingerprint',
        'password',
        'published_at',
        'expires_at',
        'name',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'type' => ResourceType::class,
            'preview_type' => ResourceType::class,
            'hidden' => 'boolean',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'password' => 'hashed',
            'is_private' => 'boolean',
        ];
    }

    /**
     * The resource payload (a link URL or paste content), transparently
     * compressed on write to save disk space. Compression is adaptive: the
     * packed form is only kept when it is actually smaller, so short URLs stay
     * raw (and human-readable) in the column.
     */
    public function data(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $this->decompressData($value),
            set: fn (?string $value) => $this->compressData($value),
        );
    }

    private function compressData(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $packed = base64_encode(gzencode($value, 9));

        return strlen($packed) < strlen($value) ? $packed : $value;
    }

    private function decompressData(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $decoded = base64_decode($value, true);

        // Not valid base64 (e.g. a link URL contains ":") → stored raw.
        if ($decoded === false || ! str_starts_with($decoded, "\x1f\x8b")) {
            return $value;
        }

        $uncompressed = gzdecode($decoded);

        return $uncompressed === false ? $value : $uncompressed;
    }

    /**
     * Whether the resource serves its content from the {@see $data} column
     * (a paste) rather than from a physical file or a redirect (a link).
     */
    public function hasInlineContent(): Attribute
    {
        return Attribute::make(get: fn () => $this->type !== ResourceType::LINK && $this->data !== null);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Whether the given user may access this resource. Private resources — and
     * expired ones, which become private once {@see $expires_at} passes — are only
     * accessible to their owner and to administrators. A password, when set, is a
     * separate gate handled via {@see requiresPasswordFor()}.
     */
    public function isAccessibleBy(?User $user): bool
    {
        if ($this->is_private || $this->isExpired()) {
            return $this->isOwnerOrAdmin($user);
        }

        return true;
    }

    /**
     * Whether the resource has passed its expiration date and is therefore no
     * longer publicly visible.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function hasPassword(): bool
    {
        return $this->password !== null;
    }

    /**
     * Whether the given user must supply the resource password before they may
     * view or download it. The owner and administrators always bypass it.
     */
    public function requiresPasswordFor(?User $user): bool
    {
        return $this->hasPassword() && ! $this->isOwnerOrAdmin($user);
    }

    /**
     * The session key under which a successful password unlock is remembered for
     * the current browser session.
     */
    public function unlockSessionKey(): string
    {
        return "resource_unlocked.{$this->id}";
    }

    public function isUnlockedIn(Session $session): bool
    {
        return (bool) $session->get($this->unlockSessionKey(), false);
    }

    /**
     * Whether the resource is password-protected and the given user has not yet
     * unlocked it in the current session. Combines the password gate with the
     * per-session unlock state so callers share a single source of truth.
     */
    public function isLockedFor(?User $user, Session $session): bool
    {
        return $this->requiresPasswordFor($user) && ! $this->isUnlockedIn($session);
    }

    private function isOwnerOrAdmin(?User $user): bool
    {
        return $user !== null && ($user->is_admin || $user->id === $this->user_id);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Resource::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Resource::class, 'parent_id');
    }

    public function rawUrl(): Attribute
    {
        return Attribute::make(get: fn () => $this->makeResourceUrl('raw.ext', $this->code, $this->extension));
    }

    public function downloadUrl(): Attribute
    {
        return Attribute::make(get: fn () => $this->makeResourceUrl('download.ext', $this->code, $this->extension));
    }

    public function previewUrl(): Attribute
    {
        return Attribute::make(get: fn () => $this->makeResourceUrl('preview', $this->code));
    }

    public function previewExtUrl(): Attribute
    {
        return Attribute::make(get: fn () => $this->makeResourceUrl('preview.ext', $this->code, $this->extension));
    }

    public function thumbnailUrl(): Attribute
    {
        return Attribute::make(get: fn () => $this->makeResourceUrl('thumbnail', $this->code));
    }

    /**
     * A permanent, signed URL that deletes the resource when requested. ShareX (and
     * similar uploaders) open it with a plain GET, so possession of the signature is
     * the authorization — no further ownership check is required.
     */
    public function deletionUrl(): Attribute
    {
        return Attribute::make(get: fn () => URL::signedRoute('resource.delete', ['resource' => $this->code]));
    }

    private function makeResourceUrl(string $route, string $resource, ?string $ext = null): string
    {
        // Resources without an extension (e.g. links) or with a harmful one fall back
        // to the extension-less route variant rather than failing URL generation.
        if ($ext === null || ResourceType::canExtensionBeHarmful($ext)) {
            return route(Str::remove('.ext', $route), ['resource' => $resource]);
        }

        return route($route, ['resource' => $resource, 'ext' => $ext]);
    }

    /**
     * The physical storage key of the file, content-addressed by fingerprint so that
     * duplicate uploads share a single stored file.
     */
    public function storagePath(): Attribute
    {
        return Attribute::make(get: fn () => $this->fingerprint);
    }

    /**
     * The physical storage key of the generated preview, shared across duplicates.
     */
    public function previewPath(): Attribute
    {
        return Attribute::make(get: fn () => "{$this->fingerprint}.preview.{$this->preview_extension}");
    }

    public function isDir(): Attribute
    {
        return Attribute::make(get: fn () => $this->type === ResourceType::DIRECTORY);
    }

    /**
     * A human-friendly label for the resource. Never derived from {@see $data},
     * which may hold a URL today and larger or non-displayable content later.
     */
    public function displayName(): Attribute
    {
        return Attribute::make(get: fn () => $this->name ?? $this->filename ?? $this->code);
    }

    public function sizeHumanReadable(): Attribute
    {
        return Attribute::make(get: fn () => $this->size ? Helpers::humanizeBytes($this->size) : null);
    }

    public function hasPreview(): Attribute
    {
        return Attribute::make(get: fn () => $this->preview_type !== null
            && $this->preview_type !== ResourceType::FUTURE);
    }

    public function previewIsPending(): Attribute
    {
        return Attribute::make(get: fn () => $this->preview_type === ResourceType::FUTURE);
    }

    public function isDisplayable(): Attribute
    {
        return Attribute::make(get: fn () => $this->type->isDisplayable($this->mime));
    }

    public function icon(): Attribute
    {
        return Attribute::make(get: fn () => $this->type->icon($this->extension));
    }

    public function iconColor(): Attribute
    {
        return Attribute::make(get: fn () => $this->type->iconColor($this->extension));
    }
}
