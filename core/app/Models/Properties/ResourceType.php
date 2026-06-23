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
    case FUTURE = '-';

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

    /**
     * List of potentially harmful file extensions for the server.
     *
     * @var list<string>
     */
    private const HARMFUL_EXTENSIONS = [
        // --- PHP & Variants ---
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar', 'phps',

        // --- Active Server Pages (Microsoft IIS) ---
        'asp', 'aspx', 'axd', 'asmx', 'ashx', 'config',

        // --- Java Server Pages & Java ---
        'jsp', 'jspx', 'wss', 'do', 'action', 'jar', 'class',

        // --- Python ---
        'py', 'pyc', 'pyd', 'pyo',

        // --- Ruby ---
        'rb', 'rhtml',

        // --- Perl & CGI ---
        'pl', 'cgi',

        // --- Node.js / JavaScript (Server-side) ---
        'js', 'jsx', 'ts', 'tsx',

        // --- ColdFusion ---
        'cfm', 'cfc',

        // --- Web Server Configuration Files (Dangerous if overwritten) ---
        'htaccess', 'htpasswd', 'ini', 'conf',

        // --- Executables / System Scripts (If the server allows execution) ---
        'sh', 'bash', 'bat', 'cmd', 'exe', 'msi', 'com', 'vbs', 'ps1',
    ];

    public static function canExtensionBeHarmful(string $ext): bool
    {
        return in_array($ext, self::HARMFUL_EXTENSIONS);
    }

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

    /**
     * Resolve the icon that best represents this resource. When an extension is
     * provided, a more specific icon (spreadsheet, archive, code, ...) is
     * preferred over the generic per-type icon.
     */
    public function icon(?string $extension = null): string
    {
        return $this->descriptor($extension)['icon'];
    }

    /**
     * Resolve the accent color (a daisyUI text-* utility class) used to tint
     * this resource's icon, kept in sync with {@see icon()}.
     */
    public function iconColor(?string $extension = null): string
    {
        return $this->descriptor($extension)['color'];
    }

    /**
     * Resolve the icon + accent color pair for this resource, preferring an
     * extension-specific descriptor over the generic per-type one.
     *
     * @return array{icon: string, color: string}
     */
    private function descriptor(?string $extension): array
    {
        if ($extension !== null) {
            $specific = self::descriptorForExtension(strtolower($extension));

            if ($specific !== null) {
                return $specific;
            }
        }

        return match ($this) {
            self::IMAGE => ['icon' => 'o-photo', 'color' => 'text-success'],
            self::VIDEO => ['icon' => 'o-video-camera', 'color' => 'text-secondary'],
            self::AUDIO => ['icon' => 'o-musical-note', 'color' => 'text-accent'],
            self::PDF => ['icon' => 'o-document-text', 'color' => 'text-error'],
            self::TEXT => ['icon' => 'o-document-text', 'color' => 'text-info'],
            self::LINK => ['icon' => 'o-link', 'color' => 'text-primary'],
            self::DIRECTORY => ['icon' => 'o-folder', 'color' => 'text-warning'],
            self::FILE, self::FUTURE => ['icon' => 'o-document', 'color' => 'text-base-content'],
        };
    }

    /**
     * Map well-known file extensions to a specific icon + accent color pair,
     * regardless of the resolved resource type. Returns null when none applies.
     *
     * @return array{icon: string, color: string}|null
     */
    private static function descriptorForExtension(string $extension): ?array
    {
        return match ($extension) {
            'xls', 'xlsx', 'xlsm', 'ods', 'csv', 'tsv' => ['icon' => 'o-table-cells', 'color' => 'text-success'],
            'doc', 'docx', 'odt', 'rtf' => ['icon' => 'o-document-text', 'color' => 'text-info'],
            'ppt', 'pptx', 'odp', 'key' => ['icon' => 'o-presentation-chart-bar', 'color' => 'text-warning'],
            'zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'xz', 'tgz' => ['icon' => 'o-archive-box', 'color' => 'text-warning'],
            'php', 'js', 'jsx', 'ts', 'tsx', 'json', 'json5', 'xml', 'yaml', 'yml',
            'html', 'htm', 'css', 'scss', 'sass', 'less', 'sql', 'sh', 'bash',
            'py', 'rb', 'go', 'rs', 'java', 'c', 'cpp', 'h', 'cs', 'swift',
            'kt', 'vue', 'toml', 'ini' => ['icon' => 'o-code-bracket', 'color' => 'text-secondary'],
            default => null,
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
                'video/quicktime',
            ], true),

            self::AUDIO => in_array($mime, [
                'audio/mpeg', // mp3
                'audio/mp4',  // aac/m4a often comes as audio/mp4
                'audio/aac',
                'audio/wav',
                'audio/x-wav',
                'audio/ogg',
                'audio/opus',
                'audio/flac',
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
