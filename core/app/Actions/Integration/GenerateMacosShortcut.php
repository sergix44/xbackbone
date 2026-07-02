<?php

namespace App\Actions\Integration;

use App\Actions\Token\IssueIntegrationToken;
use App\Models\User;

class GenerateMacosShortcut
{
    public function __construct(private IssueIntegrationToken $issueIntegrationToken) {}

    /**
     * Build a self-contained macOS "Share" installer for the given user.
     *
     * Returns a single shell script that embeds the xbb uploader and a Shortcut (Comandi Rapidi),
     * plus a freshly issued personal token. Running it registers an "Upload to {app name}" entry
     * in the macOS Share sheet: the shortcut runs xbb, which reads the shared configuration the
     * installer writes to ~/.config/xbackbone/config.
     */
    public function __invoke(User $user): string
    {
        $token = ($this->issueIntegrationToken)($user, 'macOS-'.now()->format('Y-m-d_H:i:s'), ['resource:upload', 'resource:delete'])->plainTextToken;

        $base = resource_path('integrations/macos');

        $name = 'Upload to '.config('app.name');
        // Strip characters a macOS filename cannot hold; the display name keeps them.
        $file = str_replace(['/', ':'], '', $name);

        // The shortcut name lives inside the plist; escape it for XML before embedding.
        $plist = str_replace(
            '@@SHORTCUT_NAME@@',
            htmlspecialchars($name, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
            (string) file_get_contents("$base/shortcut.plist.stub")
        );

        // strtr does a single, non-overlapping pass, and the base64 payloads are opaque, so an
        // embedded payload can never be re-interpreted as another placeholder.
        return strtr((string) file_get_contents("$base/installer.sh.stub"), [
            '@@XBB_URL@@' => rtrim((string) config('app.url'), '/'),
            '@@XBB_TOKEN@@' => $token,
            '@@SHORTCUT_NAME@@' => $name,
            '@@SHORTCUT_FILE@@' => $file,
            '@@XBB_SCRIPT_B64@@' => base64_encode((string) file_get_contents(resource_path('integrations/xbb'))),
            '@@SHORTCUT_PLIST_B64@@' => base64_encode($plist),
        ]);
    }
}
