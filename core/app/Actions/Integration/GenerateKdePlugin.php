<?php

namespace XBB\Actions\Integration;

use XBB\Actions\Token\IssueIntegrationToken;
use XBB\Models\User;

class GenerateKdePlugin
{
    public function __construct(private IssueIntegrationToken $issueIntegrationToken) {}

    /**
     * Build a self-contained KDE "Share" (Purpose) plugin installer for the given user.
     *
     * Returns a single shell script that embeds the plugin metadata (with the instance name),
     * the Python uploader and the icons, plus a freshly issued personal token. Running it adds
     * an "Upload to {app name}" entry to the KDE Share menu (Spectacle, Dolphin, and any other
     * Purpose-aware app).
     */
    public function __invoke(User $user): string
    {
        $token = ($this->issueIntegrationToken)($user, 'KDE-'.now()->format('Y-m-d_H:i:s'), ['resource:upload'])->plainTextToken;

        $base = resource_path('integrations/kde');

        // The menu label ("Upload to {app name}") lives in metadata.json; escape it for JSON.
        $appName = trim((string) json_encode((string) config('app.name'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), '"');
        $metadata = str_replace('@@APP_NAME@@', $appName, (string) file_get_contents("$base/metadata.json"));

        // strtr does a single, non-overlapping pass, so an embedded payload can never be
        // re-interpreted as another placeholder.
        return strtr((string) file_get_contents("$base/installer.sh.stub"), [
            '@@XBB_URL@@' => rtrim((string) config('app.url'), '/'),
            '@@XBB_TOKEN@@' => $token,
            '@@METADATA_JSON@@' => rtrim($metadata),
            '@@MAIN_PY@@' => rtrim((string) file_get_contents("$base/contents/code/main.py")),
            '@@ICON_32_B64@@' => base64_encode((string) file_get_contents("$base/icons/xbackbone-32.png")),
            '@@ICON_192_B64@@' => base64_encode((string) file_get_contents("$base/icons/xbackbone-192.png")),
            '@@ICON_512_B64@@' => base64_encode((string) file_get_contents("$base/icons/xbackbone-512.png")),
        ]);
    }
}
