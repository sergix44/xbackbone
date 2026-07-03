<?php

namespace XBB\Installer\Support;

enum DatabaseDriver: string
{
    case SQLITE = 'sqlite';
    case MYSQL = 'mysql';
    case MARIADB = 'mariadb';
    case PGSQL = 'pgsql';

    public function label(): string
    {
        return match ($this) {
            self::SQLITE => 'SQLite',
            self::MYSQL => 'MySQL',
            self::MARIADB => 'MariaDB',
            self::PGSQL => 'PostgreSQL',
        };
    }

    /**
     * Default TCP port for server-based drivers, or null for SQLite.
     */
    public function defaultPort(): ?int
    {
        return match ($this) {
            self::SQLITE => null,
            self::PGSQL => 5432,
            default => 3306,
        };
    }

    /**
     * Whether the driver connects to a database server (vs. a local file).
     */
    public function isServer(): bool
    {
        return $this !== self::SQLITE;
    }

    /**
     * Options for a Mary select/radio, derived from the cases.
     *
     * @return list<array{id: string, name: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $driver): array => ['id' => $driver->value, 'name' => $driver->label()],
            self::cases(),
        );
    }
}
