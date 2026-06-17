<?php

namespace app\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Resource
 */
class ResourceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'type' => $this->type,
            'filename' => $this->filename,
            'mime' => $this->mime,
            'size' => $this->size,
            'is_private' => $this->is_private,
            'extension' => $this->extension,
            'view_count' => $this->views,
            'download_count' => $this->downloads,
            'preview_url' => $this->preview_url,
            'preview_ext_url' => $this->preview_ext_url,
            'raw_url' => $this->raw_url,
            'published_at' => $this->published_at,
            'expires_at' => $this->expires_at,
        ];
    }
}
