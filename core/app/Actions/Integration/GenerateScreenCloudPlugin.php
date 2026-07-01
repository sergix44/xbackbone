<?php

namespace App\Actions\Integration;

use App\Models\User;
use RuntimeException;
use ZipArchive;

class GenerateScreenCloudPlugin
{
    /**
     * Build a ScreenCloud uploader plugin package (ZIP) for the given user, with a
     * freshly issued personal token and the instance URL baked into its config.json.
     */
    public function __invoke(User $user): string
    {
        $token = $user->createToken('ScreenCloud-'.now()->format('Y-m-d_H:i:s'), ['resource:upload', 'resource:delete'])->plainTextToken;

        $config = [
            'token' => $token,
            'host' => rtrim(config('app.url'), '/'),
        ];

        $path = tempnam(sys_get_temp_dir(), 'screencloud');

        try {
            $zip = new ZipArchive;
            if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Unable to create the ScreenCloud plugin archive.');
            }

            $base = resource_path('integrations/screencloud');
            $zip->addFile("$base/main.py", 'main.py');
            $zip->addFile("$base/metadata.xml", 'metadata.xml');
            $zip->addFile("$base/settings.ui", 'settings.ui');
            $zip->addFile("$base/icon.png", 'icon.png');
            $zip->addFromString('config.json', json_encode($config, JSON_UNESCAPED_SLASHES));
            $zip->close();

            return file_get_contents($path);
        } finally {
            @unlink($path);
        }
    }
}
