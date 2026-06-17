<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntegrationController extends Controller
{
    /**
     * Token name used for the ShareX custom uploader.
     */
    private const SHAREX_TOKEN_NAME = 'ShareX';

    /**
     * Download a ready-to-import ShareX custom uploader (.sxcu) configuration.
     *
     * The user's ShareX token is rotated on every download so the generated
     * config always carries a fresh, working bearer token. Re-downloading the
     * config therefore invalidates any previously issued ShareX configuration.
     */
    public function shareX(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->tokens()->where('name', self::SHAREX_TOKEN_NAME)->delete();
        $token = $user->createToken(self::SHAREX_TOKEN_NAME)->plainTextToken;

        $config = [
            'Version' => '17.0.0',
            'Name' => config('app.name').' - '.$user->name,
            'DestinationType' => 'ImageUploader, TextUploader, FileUploader',
            'RequestMethod' => 'POST',
            'RequestURL' => route('api.v1.upload'),
            'Headers' => [
                'Authorization' => 'Bearer '.$token,
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

        $fileName = str($user->name)->slug().'-sharex.sxcu';

        return response()->json(
            $config,
            200,
            ['Content-Disposition' => 'attachment; filename="'.$fileName.'"'],
            JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
    }
}
