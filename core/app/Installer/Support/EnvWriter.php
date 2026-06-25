<?php

namespace App\Installer\Support;

/**
 * Minimal, dependency-free `.env` editor used by the installer.
 *
 * It performs a line-oriented upsert that preserves comments, ordering and the
 * file's line endings, and writes atomically via a temporary file so a crash
 * mid-write cannot corrupt the environment file.
 */
final class EnvWriter
{
    public function __construct(private readonly string $path) {}

    public static function forApp(): self
    {
        return new self(app()->environmentFilePath());
    }

    /**
     * Upsert the given key => value pairs into the env file.
     *
     * @param  array<string, scalar|null>  $values
     */
    public function set(array $values): void
    {
        $contents = is_file($this->path) ? (string) file_get_contents($this->path) : '';
        $eol = str_contains($contents, "\r\n") ? "\r\n" : "\n";
        $lines = $contents === '' ? [] : explode($eol, $contents);

        foreach ($values as $key => $value) {
            $formatted = $key.'='.$this->format($value);
            $pattern = '/^(\s*)(export\s+)?'.preg_quote($key, '/').'\s*=/';
            $replaced = false;

            foreach ($lines as $index => $line) {
                if (preg_match($pattern, $line) === 1) {
                    $lines[$index] = $formatted;
                    $replaced = true;
                    break;
                }
            }

            if (! $replaced) {
                $lines[] = $formatted;
            }
        }

        $output = implode($eol, $lines);

        if (! str_ends_with($output, $eol)) {
            $output .= $eol;
        }

        $temp = $this->path.'.tmp';
        file_put_contents($temp, $output, LOCK_EX);
        @chmod($temp, is_file($this->path) ? (fileperms($this->path) & 0777) : 0644);
        rename($temp, $this->path);
    }

    /**
     * Read the raw value for a key, or null when it is absent.
     */
    public function get(string $key): ?string
    {
        if (! is_file($this->path)) {
            return null;
        }

        foreach (file($this->path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            if (preg_match('/^(\s*)(export\s+)?'.preg_quote($key, '/').'\s*=(.*)$/', $line, $matches) === 1) {
                return trim($matches[3], " \t\"'");
            }
        }

        return null;
    }

    /**
     * Format a value for safe inclusion in the env file.
     *
     * Single quotes are preferred when quoting is required because phpdotenv
     * treats single-quoted values literally and never interpolates `${VAR}` or
     * `$VAR` sequences — important for passwords containing `$`.
     */
    private function format(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        $value = (string) $value;

        // Safe characters (incl. base64 app-key alphabet) can stay unquoted.
        if (preg_match('/^[A-Za-z0-9_.\/:@+=-]+$/', $value) === 1) {
            return $value;
        }

        if (! str_contains($value, "'")) {
            return "'".$value."'";
        }

        return '"'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value).'"';
    }
}
