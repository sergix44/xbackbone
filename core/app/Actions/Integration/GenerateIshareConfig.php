<?php

namespace App\Actions\Integration;

use App\Actions\Token\IssueIntegrationToken;
use App\Models\User;

class GenerateIshareConfig
{
    public function __construct(private IssueIntegrationToken $issueIntegrationToken) {}

    /**
     * Build an ishare custom-uploader (.iscu) configuration, per the spec 2.0.0+.
     *
     * ishare is the macOS counterpart to ShareX: one downloaded JSON file points the
     * app at this instance. The result and deletion links are built from the upload
     * response with ishare's {{dotted.path}} placeholders, resolved against our
     * `data`-wrapped payload (deletion is a signed GET, hence deleteRequestType GET).
     */
    public function __invoke(User $user): array
    {
        $token = ($this->issueIntegrationToken)($user, 'ishare-'.now()->format('Y-m-d_H:i:s'), ['resource:upload', 'resource:delete'])->plainTextToken;

        return [
            'name' => config('app.name').' - '.$user->name,
            'requestURL' => route('api.v1.upload'),
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
            'fileFormName' => 'file',
            'responseURL' => '{{data.preview_ext_url}}',
            'deletionURL' => '{{data.deletion_url}}',
            'deleteRequestType' => 'GET',
        ];
    }
}
