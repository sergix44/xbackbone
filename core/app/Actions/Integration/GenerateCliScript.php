<?php

namespace App\Actions\Integration;

use App\Models\User;
use Illuminate\Support\Str;

class GenerateCliScript
{
    /**
     * Build a ready-to-run CLI uploader script for the given user, with the instance
     * URL and a freshly issued personal token baked into its configuration sentinels.
     */
    public function __invoke(User $user): string
    {
        $now = now()->format('Y-m-d_H:i:s');
        $token = $user->createToken("CLI-$now", ['resource:upload', 'resource:delete'])->plainTextToken;

        $template = file_get_contents(resource_path('integrations/xbb'));

        return Str::of($template)
            ->replace('@@XBB_URL@@', rtrim(config('app.url'), '/'))
            ->replace('@@XBB_TOKEN@@', $token)
            ->value();
    }
}
