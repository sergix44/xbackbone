<?php

namespace XBB\Installer\Support;

enum StorageDriver: string
{
    case LOCAL = 'local';
    case S3 = 's3';
    case FTP = 'ftp';
    case SFTP = 'sftp';

    public function label(): string
    {
        return match ($this) {
            self::LOCAL => 'Local filesystem',
            self::S3 => 'Amazon S3 (or compatible)',
            self::FTP => 'FTP',
            self::SFTP => 'SFTP',
        };
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
