<?php

namespace App\Http\Controllers;

use App\Actions\Integration\GenerateSharexConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntegrationController extends Controller
{
    /**
     * Download a ready-to-import ShareX custom uploader (.sxcu) configuration.
     */
    public function shareX(GenerateSharexConfig $generateSharexConfig): JsonResponse
    {
        $user = auth()->user();
        $config = $generateSharexConfig($user);
        $fileName = str($user->name)->slug().'-sharex.sxcu';

        return response()->json(
            $config,
            200,
            ['Content-Disposition' => 'attachment; filename="'.$fileName.'"'],
            JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
    }
}
