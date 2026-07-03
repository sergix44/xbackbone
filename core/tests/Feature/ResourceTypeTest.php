<?php

use XBB\Models\Properties\ResourceType;

dataset('textual mimes', [
    'plain text' => ['text/plain'],
    'html' => ['text/html'],
    'css' => ['text/css'],
    'json' => ['application/json'],
    'ld+json' => ['application/ld+json'],
    'javascript' => ['application/javascript'],
    'x-javascript' => ['application/x-javascript'],
    'typescript' => ['application/typescript'],
    'xml' => ['application/xml'],
    'xhtml+xml' => ['application/xhtml+xml'],
    'yaml' => ['application/yaml'],
    'x-yaml' => ['application/x-yaml'],
    'sql' => ['application/sql'],
    'shell script' => ['application/x-sh'],
    'php' => ['application/x-httpd-php'],
    'json with charset' => ['application/json; charset=utf-8'],
    'uppercase json' => ['APPLICATION/JSON'],
]);

dataset('binary mimes', [
    'octet stream' => ['application/octet-stream'],
    'zip' => ['application/zip'],
    'gzip' => ['application/gzip'],
    'msword' => ['application/msword'],
]);

it('classifies textual mimes as text', function (string $mime) {
    expect(ResourceType::fromMime($mime))->toBe(ResourceType::TEXT)
        ->and(ResourceType::TEXT->isDisplayable($mime))->toBeTrue();
})->with('textual mimes');

it('does not classify binary mimes as text', function (string $mime) {
    expect(ResourceType::fromMime($mime))->toBe(ResourceType::FILE);
})->with('binary mimes');

it('classifies primary media types correctly', function () {
    expect(ResourceType::fromMime('image/png'))->toBe(ResourceType::IMAGE)
        ->and(ResourceType::fromMime('video/mp4'))->toBe(ResourceType::VIDEO)
        ->and(ResourceType::fromMime('audio/mpeg'))->toBe(ResourceType::AUDIO)
        ->and(ResourceType::fromMime('application/pdf'))->toBe(ResourceType::PDF);
});

it('resolves a per-type icon when no extension is given', function () {
    expect(ResourceType::IMAGE->icon())->toBe('o-photo')
        ->and(ResourceType::VIDEO->icon())->toBe('o-video-camera')
        ->and(ResourceType::AUDIO->icon())->toBe('o-musical-note')
        ->and(ResourceType::PDF->icon())->toBe('o-document-text')
        ->and(ResourceType::TEXT->icon())->toBe('o-document-text')
        ->and(ResourceType::LINK->icon())->toBe('o-link')
        ->and(ResourceType::DIRECTORY->icon())->toBe('o-folder')
        ->and(ResourceType::FILE->icon())->toBe('o-document');
});

it('prefers an extension-specific icon when available', function (string $extension, string $icon) {
    expect(ResourceType::FILE->icon($extension))->toBe($icon);
})->with([
    'excel' => ['xlsx', 'o-table-cells'],
    'csv' => ['CSV', 'o-table-cells'],
    'word' => ['docx', 'o-document-text'],
    'powerpoint' => ['pptx', 'o-presentation-chart-bar'],
    'zip archive' => ['zip', 'o-archive-box'],
    'tarball' => ['tar', 'o-archive-box'],
    'php source' => ['php', 'o-code-bracket'],
    'json source' => ['json', 'o-code-bracket'],
]);

it('falls back to the per-type icon for unknown extensions', function () {
    expect(ResourceType::FILE->icon('bin'))->toBe('o-document')
        ->and(ResourceType::IMAGE->icon('png'))->toBe('o-photo');
});

it('resolves an accent color per type', function () {
    expect(ResourceType::VIDEO->iconColor())->toBe('text-secondary')
        ->and(ResourceType::PDF->iconColor())->toBe('text-error')
        ->and(ResourceType::DIRECTORY->iconColor())->toBe('text-warning');
});

it('keeps icon and color extension overrides in sync', function () {
    expect(ResourceType::FILE->iconColor('xlsx'))->toBe('text-success')
        ->and(ResourceType::FILE->iconColor('zip'))->toBe('text-warning')
        ->and(ResourceType::FILE->iconColor('php'))->toBe('text-secondary');
});
