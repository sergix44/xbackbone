<?php

namespace XBB\Support;

enum ActivityCategory: string
{
    case RESOURCE = 'resource';
    case USER = 'user';
    case TOKEN = 'token';
    case PASSKEY = 'passkey';
    case AUTH = 'auth';

    /**
     * Human-readable label for the category, used to populate the activity
     * feed's filter.
     */
    public function label(): string
    {
        return match ($this) {
            self::RESOURCE => __('Files'),
            self::USER => __('Users'),
            self::TOKEN => __('API tokens'),
            self::PASSKEY => __('Passkeys'),
            self::AUTH => __('Authentication'),
        };
    }

    /**
     * Options for a Mary select, derived from the cases.
     *
     * @return list<array{id: string, name: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $category): array => ['id' => $category->value, 'name' => $category->label()],
            self::cases(),
        );
    }
}
