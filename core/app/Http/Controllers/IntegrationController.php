<?php

namespace App\Http\Controllers;

use App\Actions\Integration\GenerateCliScript;
use App\Actions\Integration\GenerateIshareConfig;
use App\Actions\Integration\GenerateKdePlugin;
use App\Actions\Integration\GenerateMacosShortcut;
use App\Actions\Integration\GenerateScreenCloudPlugin;
use App\Actions\Integration\GenerateSharexConfig;
use App\Models\User;
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
     * Download a ready-to-import ishare custom uploader (.iscu) configuration, the macOS
     * counterpart to the ShareX config.
     */
    public function ishare(GenerateIshareConfig $generateIshareConfig): JsonResponse
    {
        $user = auth()->user();
        $config = $generateIshareConfig($user);
        $fileName = str($user->name)->slug().'-ishare.iscu';

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

    /**
     * Download the self-contained KDE "Share" (Purpose) plugin installer, pre-filled with the
     * user's token, so the instance shows up under Share in Spectacle, Dolphin and other KDE apps.
     */
    public function kde(GenerateKdePlugin $generateKdePlugin): Response
    {
        return response($generateKdePlugin(auth()->user()), 200, [
            'Content-Type' => 'text/x-shellscript; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="xbackbone-kde-install.sh"',
        ]);
    }

    /**
     * Download the self-contained macOS "Share" installer, pre-filled with the user's token, so a
     * Shortcut that uploads to the instance appears in the macOS Share sheet.
     */
    public function macos(GenerateMacosShortcut $generateMacosShortcut): Response
    {
        return response($generateMacosShortcut(auth()->user()), 200, [
            'Content-Type' => 'text/x-shellscript; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="xbackbone-macos-install.sh"',
        ]);
    }

    /**
     * Serve the ScreenCloud uploader plugin package. Fetched over a permanent signed
     * URL (no session) so ScreenCloud can install it directly from the pasted link.
     */
    public function screenCloud(User $user, GenerateScreenCloudPlugin $generateScreenCloudPlugin): Response
    {
        $fileName = str($user->name)->slug().'-screencloud.zip';

        return response($generateScreenCloudPlugin($user), 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }
}
