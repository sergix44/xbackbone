<?php

namespace App\Support;

use Composer\InstalledVersions;
use Illuminate\Support\Facades\Cache;

/**
 * Encapsulates the self-upgrade gate, version resolution and the run-status
 * shared between the web request that launches an upgrade and the detached
 * process that performs it.
 */
class Updater
{
    private const STATUS_KEY = 'xbb-upgrade-status';

    /**
     * Whether self-upgrade is available. It only makes sense on a real
     * deployment installed through the skeleton (which exports APP_ROOT) and
     * never in the local monorepo dev setup.
     */
    public function isSupported(): bool
    {
        return app()->isProduction() && $this->appRoot() !== null;
    }

    /**
     * The skeleton root, where the composer.json requiring the core lives.
     */
    public function appRoot(): ?string
    {
        $root = getenv('APP_ROOT');

        return is_string($root) && $root !== '' ? $root : null;
    }

    /**
     * The currently installed core version. Falls back to "dev-master" when
     * the package is not installed as a dependency (local dev / tests).
     */
    public function currentVersion(): string
    {
        $package = config('updater.package');

        if (InstalledVersions::isInstalled($package)) {
            return InstalledVersions::getPrettyVersion($package) ?? 'dev-master';
        }

        return 'dev-master';
    }

    /**
     * Whether the given version is a development branch or a pre-release, and
     * therefore not directly comparable as a stable semver tag.
     */
    public function isDevVersion(string $version): bool
    {
        return str_starts_with($version, 'dev-') || str_contains($version, '-');
    }

    /**
     * @return array{state: string, target: ?string, message: ?string, finishedAt: ?string}
     */
    public function status(): array
    {
        return Cache::get(self::STATUS_KEY, [
            'state' => 'idle',
            'target' => null,
            'message' => null,
            'finishedAt' => null,
        ]);
    }

    public function isRunning(): bool
    {
        return $this->status()['state'] === 'running';
    }

    public function markRunning(string $target): void
    {
        Cache::put(self::STATUS_KEY, [
            'state' => 'running',
            'target' => $target,
            'message' => null,
            'finishedAt' => null,
        ], now()->addHour());
    }

    public function markDone(string $version): void
    {
        Cache::put(self::STATUS_KEY, [
            'state' => 'done',
            'target' => $version,
            'message' => __('Upgraded to :version', ['version' => $version]),
            'finishedAt' => now()->toDateTimeString(),
        ], now()->addDay());
    }

    public function markFailed(string $message): void
    {
        Cache::put(self::STATUS_KEY, [
            'state' => 'failed',
            'target' => null,
            'message' => $message,
            'finishedAt' => now()->toDateTimeString(),
        ], now()->addDay());
    }

    public function logPath(): string
    {
        return storage_path('app/upgrade.log');
    }

    public function resetLog(): void
    {
        @file_put_contents($this->logPath(), '');
    }

    /**
     * Return the last $lines lines of the upgrade log.
     */
    public function tailLog(int $lines = 80): string
    {
        $path = $this->logPath();

        if (! is_file($path)) {
            return '';
        }

        $content = rtrim((string) file_get_contents($path), "\r\n");

        if ($content === '') {
            return '';
        }

        return implode("\n", array_slice(preg_split('/\r\n|\r|\n/', $content) ?: [], -$lines));
    }
}
