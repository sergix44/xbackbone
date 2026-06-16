<?php

namespace App\Models\Properties;

use Illuminate\Support\Str;

enum ResourceType: string
{
    case IMAGE = 'IMAGE';
    case VIDEO = 'VIDEO';
    case AUDIO = 'AUDIO';
    case PDF = 'PDF';
    case TEXT = 'TEXT';
    case FILE = 'FILE';
    case LINK = 'LINK';
    case DIRECTORY = 'DIRECTORY';

    /**
     * Non-"text/*" mime types whose content is still plain text and can be
     * rendered directly in the browser (json, js, xml, yaml, shell scripts, ...).
     *
     * @var list<string>
     */
    private const TEXTUAL_MIMES = [
        'application/json',
        'application/json5',
        'application/javascript',
        'application/x-javascript',
        'application/ecmascript',
        'application/typescript',
        'application/x-typescript',
        'application/xml',
        'application/xhtml+xml',
        'application/yaml',
        'application/x-yaml',
        'application/toml',
        'application/csv',
        'application/sql',
        'application/graphql',
        'application/x-sh',
        'application/x-shellscript',
        'application/x-httpd-php',
        'application/x-php',
        'application/x-latex',
        'application/x-tex',
    ];

    public static function fromMime(string $mime): self
    {
        $mime = self::normalizeMime($mime);

        $data = explode('/', $mime);
        $type = $data[0];
        $subtype = $data[1] ?? '';

        return match (true) {
            $type === 'image' => self::IMAGE,
            $type === 'video' => self::VIDEO,
            $type === 'audio' => self::AUDIO,
            Str::contains($subtype, ['pdf', 'x-pdf']) => self::PDF,
            self::isTextualMime($mime) => self::TEXT,
            default => self::FILE,
        };
    }

    /**
     * Normalize a mime type by lowercasing it and stripping any parameters
     * such as "; charset=utf-8".
     */
    private static function normalizeMime(string $mime): string
    {
        return strtolower(trim(explode(';', $mime, 2)[0]));
    }

    /**
     * Whether the given (already normalized) mime represents textual content,
     * including "text/*", known textual "application/*" types, and structured
     * syntax suffixes like "+json" or "+xml".
     */
    private static function isTextualMime(string $mime): bool
    {
        return str_starts_with($mime, 'text/')
            || in_array($mime, self::TEXTUAL_MIMES, true)
            || Str::endsWith($mime, ['+json', '+xml', '+yaml']);
    }

    public static function fromValue(string $value): self
    {
        return match (true) {
            Str::startsWith($value, 'http') => self::LINK,
            default => self::FILE,
        };
    }

    public function isDisplayable(string $mime): bool
    {
        $mime = self::normalizeMime($mime); // strips "; charset=..."

        // only types that can be displayed directly by the browser (commonly)
        return match ($this) {
            self::IMAGE => in_array($mime, [
                'image/apng',
                'image/avif',
                'image/bmp',
                'image/gif',
                'image/jpeg',
                'image/png',
                'image/svg+xml',
                'image/webp',
                'image/x-icon',
                'image/vnd.microsoft.icon',
            ], true),

            // Note: browser support depends on codecs; these are the most common HTML5-friendly ones
            self::VIDEO => in_array($mime, [
                'video/mp4',
                'video/webm',
                'video/ogg',
            ], true),

            self::AUDIO => in_array($mime, [
                'audio/mpeg', // mp3
                'audio/mp4',  // aac/m4a often comes as audio/mp4
                'audio/aac',
                'audio/wav',
                'audio/ogg',
                'audio/opus',
            ], true),

            self::PDF => in_array($mime, [
                'application/pdf',
                'application/x-pdf',
            ]),

            // Many text/* are displayable, but this is "renderable", not necessarily "safe to inline"
            self::TEXT => self::isTextualMime($mime),

            default => false,
        };
    }
}
