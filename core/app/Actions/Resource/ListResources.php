<?php

namespace App\Actions\Resource;

use App\Models\Resource;
use App\Models\User;
use Illuminate\Pagination\AbstractPaginator;
use Spatie\QueryBuilder\QueryBuilder;

class ListResources
{
    public function __invoke(?User $user = null, int $perPage = 20): AbstractPaginator
    {
        return QueryBuilder::for(Resource::class)
            ->allowedFilters(['filename', 'type', 'mime', 'extension', 'code'])
            ->allowedSorts(['created_at', 'size', 'filename', 'views', 'downloads'])
            ->defaultSort('-created_at')
            ->when($user, function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->paginate($perPage)
            ->appends(request()->query());
    }
}
