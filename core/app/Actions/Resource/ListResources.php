<?php

namespace App\Actions\Resource;

use App\Models\Resource;
use App\Models\User;
use Illuminate\Pagination\AbstractPaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class ListResources
{
    public function __invoke(?User $user = null, int $perPage = 20): AbstractPaginator
    {
        return QueryBuilder::for(Resource::class)
            ->allowedFilters([
                // Single search box across the same fields display_name falls back through.
                AllowedFilter::callback('search', function ($query, $value) {
                    $term = '%'.$value.'%';
                    $query->where(fn ($inner) => $inner
                        ->where('name', 'like', $term)
                        ->orWhere('filename', 'like', $term)
                        ->orWhere('code', 'like', $term));
                }),
                'filename', 'type', 'mime', 'extension', 'code',
            ])
            ->allowedSorts([
                'created_at', 'size', 'views', 'downloads',
                // Sort "name" by the same fallback shown on the cards (display_name).
                AllowedSort::callback('name', function ($query, bool $descending) {
                    $direction = $descending ? 'desc' : 'asc';
                    $query->orderByRaw("COALESCE(name, filename, code) {$direction}");
                }),
            ])
            ->defaultSort('-created_at')
            ->when($user, function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->paginate($perPage)
            ->appends(request()->query());
    }
}
