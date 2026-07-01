<?php

namespace App\Actions\Admin;

use App\Support\Updater;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class CheckForUpdate
{
    private const LATEST_KEY = 'xbb-latest-version';

    public function __construct(private readonly Updater $updater) {}

    /**
     * Resolve the current version and the latest stable release on Packagist.
     *
     * @return array{current: string, latest: ?string, updateAvailable: bool}
     */
    public function __invoke(bool $force = false): array
    {
        $current = $this->updater->currentVersion();
        $latest = $this->latestStableVersion($force);

        $updateAvailable = $latest !== null
            && ($this->updater->isDevVersion($current) || version_compare($latest, $current, '>'));

        return [
            'current' => $current,
            'latest' => $latest,
            'updateAvailable' => $updateAvailable,
        ];
    }

    /**
     * Cached latest-stable lookup. Failures are never cached so a transient
     * Packagist outage does not pin a null result for the whole TTL.
     */
    private function latestStableVersion(bool $force): ?string
    {
        if ($force) {
            Cache::forget(self::LATEST_KEY);
        }

        $cached = Cache::get(self::LATEST_KEY);

        if (is_string($cached)) {
            return $cached;
        }

        $latest = $this->fetchLatestStableVersion();

        if ($latest !== null) {
            Cache::put(self::LATEST_KEY, $latest, config('updater.cache_ttl'));
        }

        return $latest;
    }

    private function fetchLatestStableVersion(): ?string
    {
        $package = config('updater.package');

        try {
            $response = Http::timeout(10)->get(config('updater.packagist_url'));
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $versions = collect($response->json('packages.'.$package, []))
            ->pluck('version')
            ->filter(fn ($version) => is_string($version))
            ->map(fn (string $version) => ltrim($version, 'vV'))
            ->reject(fn (string $version) => $version === '' || $this->updater->isDevVersion($version))
            ->values();

        if ($versions->isEmpty()) {
            return null;
        }

        return $versions->sort(fn (string $a, string $b) => version_compare($a, $b))->last();
    }
}
