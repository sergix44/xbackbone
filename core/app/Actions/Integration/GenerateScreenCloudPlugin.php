<?php

namespace XBB\Actions\Integration;

use Illuminate\Support\Str;
use RuntimeException;
use XBB\Actions\Token\IssueIntegrationToken;
use XBB\Models\User;
use ZipArchive;

class GenerateScreenCloudPlugin
{
    public function __construct(private IssueIntegrationToken $issueIntegrationToken) {}

    /**
     * Build a ScreenCloud uploader plugin package (ZIP) for the given user, with a
     * freshly issued personal token and the instance URL baked into its config.json.
     *
     * The plugin's name, Python class and shortname are derived from `app.name` so
     * that multiple XBackBone instances can be installed as distinct ScreenCloud
     * plugins side by side, instead of colliding on the same identifier.
     */
    public function __invoke(User $user): string
    {
        $token = ($this->issueIntegrationToken)($user, 'ScreenCloud-'.now()->format('Y-m-d_H:i:s'), ['resource:upload'])->plainTextToken;

        $appName = config('app.name');
        $className = $this->classNameFor($appName);

        $config = [
            'token' => $token,
            'host' => rtrim(config('app.url'), '/'),
        ];

        $base = resource_path('integrations/screencloud');

        $metadata = Str::of(file_get_contents("$base/metadata.xml"))
            ->replace('@@SC_APP_NAME@@', htmlspecialchars($appName, ENT_XML1 | ENT_QUOTES, 'UTF-8'))
            ->replace('@@SC_SHORT_NAME@@', $this->shortNameFor($appName))
            ->replace('@@SC_CLASS_NAME@@', $className)
            ->value();

        $mainScript = Str::of(file_get_contents("$base/main.py"))
            ->replace('@@SC_APP_NAME@@', str_replace(['\\', "'"], ['\\\\', "\\'"], $appName))
            ->replace('@@SC_CLASS_NAME@@', $className)
            ->value();

        $path = tempnam(sys_get_temp_dir(), 'screencloud');

        try {
            $zip = new ZipArchive;
            if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Unable to create the ScreenCloud plugin archive.');
            }

            $zip->addFromString('main.py', $mainScript);
            $zip->addFromString('metadata.xml', $metadata);
            $zip->addFile("$base/settings.ui", 'settings.ui');
            $zip->addFile("$base/icon.png", 'icon.png');
            $zip->addFromString('config.json', json_encode($config, JSON_UNESCAPED_SLASHES));
            $zip->close();

            return file_get_contents($path);
        } finally {
            @unlink($path);
        }
    }

    /**
     * A valid, instance-unique Python class name for the plugin, e.g. "XBackBoneUploader".
     */
    private function classNameFor(string $appName): string
    {
        $safe = preg_replace('/[^A-Za-z0-9_]/', '', Str::studly(Str::ascii($appName))) ?: 'XBackBone';

        if (preg_match('/^[0-9]/', $safe)) {
            $safe = 'Xbb'.$safe;
        }

        return $safe.'Uploader';
    }

    /**
     * A compact, instance-unique ScreenCloud plugin identifier, e.g. "xbackbone".
     */
    private function shortNameFor(string $appName): string
    {
        return Str::slug($appName, '') ?: 'xbackbone';
    }
}
