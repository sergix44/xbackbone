<?php

namespace XBB\Actions\Integration;

use XBB\Actions\Token\IssueIntegrationToken;
use XBB\Models\User;
use Illuminate\Support\Str;

class GenerateCliScript
{
    public function __construct(private IssueIntegrationToken $issueIntegrationToken) {}

    /**
     * Build a ready-to-run CLI uploader script for the given user, with the instance
     * URL and a freshly issued personal token baked into its configuration sentinels.
     */
    public function __invoke(User $user): string
    {
        $now = now()->format('Y-m-d_H:i:s');
        $token = ($this->issueIntegrationToken)($user, "CLI-$now", ['resource:upload', 'resource:delete'])->plainTextToken;

        $template = file_get_contents(resource_path('integrations/xbb'));

        return Str::of($template)
            ->replace('@@XBB_URL@@', rtrim(config('app.url'), '/'))
            ->replace('@@XBB_TOKEN@@', $token)
            ->value();
    }
}
