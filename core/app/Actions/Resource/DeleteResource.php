<?php

namespace App\Actions\Resource;

use App\Events\Resource\ResourceDeleted;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteResource
{
    public function __invoke(Resource $resource, ?User $causer = null): void
    {
        DB::transaction(function () use ($resource) {
            // Files are content-addressed and shared between duplicates: only remove the
            // physical file (and its preview) when this is the last resource referencing it.
            if ($this->isLastReference($resource)) {
                Storage::delete($resource->storage_path);

                if ($resource->has_preview) {
                    Storage::delete($resource->preview_path);
                }
            }

            $resource->delete();
        });

        event(new ResourceDeleted($resource, $causer));
    }

    private function isLastReference(Resource $resource): bool
    {
        if ($resource->fingerprint === null) {
            return false; // nothing physical to delete (e.g. a directory)
        }

        return Resource::query()
            ->where('fingerprint', $resource->fingerprint)
            ->whereKeyNot($resource->getKey())
            ->doesntExist();
    }
}
