<?php

namespace XBB\Installer\Support;

/**
 * Tracks whether the application has completed the first-run web installer.
 *
 * The on-disk marker is the trusted runtime signal because it is read with a
 * raw filesystem call and is therefore immune to config caching. The
 * {@see config()} flag mirrors it so a `config:cache`d deployment can resolve
 * the state without touching the filesystem.
 */
final class InstallationState
{
    /**
     * Absolute path to the installation marker file.
     */
    public static function markerPath(): string
    {
        return storage_path('installed');
    }

    /**
     * Whether the installer has already been completed.
     */
    public static function isInstalled(): bool
    {
        // Config is checked first as the cheap fast-path for an installed app;
        // the on-disk marker is the fallback that survives a stale config cache.
        return (bool) config('app.installed', false) || is_file(self::markerPath());
    }

    /**
     * Write the marker, permanently deactivating the installer.
     */
    public static function lock(): void
    {
        @file_put_contents(self::markerPath(), now()->toIso8601String().PHP_EOL);
    }

    /**
     * Remove the marker (used by tests and the uninstall workflow).
     */
    public static function unlock(): void
    {
        if (is_file(self::markerPath())) {
            @unlink(self::markerPath());
        }
    }
}
