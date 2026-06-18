<?php

namespace App\Actions\Integration;

use App\Models\User;
use Illuminate\Support\Str;

class GenerateSharexConfig
{
    public function __invoke(User $user): array
    {
        $now = now()->format('Y-m-d_H:i:s');
        $token = $user->createToken("ShareX-$now", ['resource:upload', 'resource:delete'])->plainTextToken;

        return [
            'Version' => '17.0.0',
            'Name' => config('app.name').' - '.$user->name,
            'DestinationType' => 'ImageUploader, TextUploader, FileUploader',
            'RequestMethod' => 'POST',
            'RequestURL' => route('api.v1.upload'),
            'Headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.Str::replace('|', '\|', $token),
            ],
            'Body' => 'MultipartFormData',
            'FileFormName' => 'file',
            'Arguments' => [
                'name' => '{filename}',
                'data' => '{input}',
            ],
            'URL' => '{json:data.preview_ext_url}',
            'ThumbnailURL' => '{json:data.raw_url}',
        ];
    }
}
