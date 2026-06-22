<?php

namespace App\Models;

use App\Models\Properties\ResourceType;
use App\Support\Helpers;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Resource extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'type',
        'user_id',
        'code',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Whether the given user may access this resource. Hidden (private) resources
     * are only accessible to their owner and to administrators.
     */
    public function isAccessibleBy(?User $user): bool
    {
        if (! $this->is_private) {
            return true;
        }

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
