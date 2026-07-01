<?php

namespace App\Http\Controllers;

use App\Actions\Integration\GenerateCliScript;
use App\Actions\Integration\GenerateSharexConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class IntegrationController extends Controller
{
    /**
     * Download a ready-to-import ShareX custom uploader (.sxcu) configuration.
     */
    public function shareX(GenerateSharexConfig $generateSharexConfig): JsonResponse
    {
        return $this->sharexConfigResponse($generateSharexConfig, 'ShareX', 'sharex');
    }

    /**
     * Download the same custom-uploader config for Xerahs, the cross-platform
     * ShareX-compatible client.
     */
    public function xerahs(GenerateSharexConfig $generateSharexConfig): JsonResponse
    {
        return $this->sharexConfigResponse($generateSharexConfig, 'Xerahs', 'xerahs');
    }

    private function sharexConfigResponse(GenerateSharexConfig $generateSharexConfig, string $client, string $suffix): JsonResponse
    {
        $user = auth()->user();
        $config = $generateSharexConfig($user, $client);
        $fileName = str($user->name)->slug()."-$suffix.sxcu";

        return response()->json(
            $config,
            200,
            ['Content-Disposition' => 'attachment; filename="'.$fileName.'"'],
            JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
    }

    /**
     * Download a ready-to-run CLI uploader script pre-filled with the user's token.
     */
    public function cli(GenerateCliScript $generateCliScript): Response
    {
        $script = $generateCliScript(auth()->user());

        return response($script, 200, [
            'Content-Type' => 'text/x-shellscript; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="xbb"',
        ]);
    }
}
