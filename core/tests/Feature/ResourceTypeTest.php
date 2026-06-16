<?php

use App\Models\Properties\ResourceType;

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
