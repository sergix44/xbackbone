<?php

namespace App\Livewire\Admin;

use App\Models\Properties\ResourceType;
use App\Models\Resource;
use App\Models\User;
use App\Support\Helpers;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Settings extends Component
{
    public string $tab;

    public function mount(string $tab = 'general'): void
    {
        $this->tab = $tab;
    }

    /**
     * System-wide, display-ready key statistics across every user and resource.
     *
     * @return array{users: string, media: string, size: string, views: string, downloads: string}
     */
    #[Computed]
    public function stats(): array
    {
        $aggregate = Resource::query()
            ->where('type', '!=', ResourceType::DIRECTORY->value)
            ->selectRaw('COUNT(*) as media, COALESCE(SUM(size), 0) as size, COALESCE(SUM(views), 0) as views, COALESCE(SUM(downloads), 0) as downloads')
            ->first();

        return [
            'users' => number_format(User::count()),
            'media' => number_format((int) $aggregate->media),
            'size' => Helpers::humanizeBytes((int) $aggregate->size),
            'views' => number_format((int) $aggregate->views),
            'downloads' => number_format((int) $aggregate->downloads),
        ];
    }

    /**
     * Per-type breakdown of uploaded media, ordered by how many resources share each type.
     *
     * @return list<array{label: string, icon: string, color: string, count: string, size: string}>
     */
    #[Computed]
    public function typeBreakdown(): array
    {
        return Resource::query()
            ->where('type', '!=', ResourceType::DIRECTORY->value)
            ->selectRaw('type, COUNT(*) as count, COALESCE(SUM(size), 0) as size')
            ->groupBy('type')
            ->orderByDesc('count')
            ->get()
            ->map(fn (Resource $row) => [
                'label' => Str::title($row->type->value),
                'icon' => $row->type->icon(),
                'color' => $row->type->iconColor(),
                'count' => number_format((int) $row->count),
                'size' => Helpers::humanizeBytes((int) $row->size),
            ])
            ->all();
    }

    /**
     * The users who have uploaded the most media, with their occupied storage.
     *
     * @return list<array{name: string, media: string, size: string}>
     */
    #[Computed]
    public function topUploaders(): array
    {
        return User::query()
            ->whereHas('resources', fn ($query) => $query->where('type', '!=', ResourceType::DIRECTORY->value))
            ->withCount(['resources as media_count' => fn ($query) => $query->where('type', '!=', ResourceType::DIRECTORY->value)])
            ->withSum(['resources as storage_used' => fn ($query) => $query->where('type', '!=', ResourceType::DIRECTORY->value)], 'size')
            ->orderByDesc('media_count')
            ->take(5)
            ->get()
            ->map(fn (User $user) => [
                'name' => $user->name,
                'media' => number_format((int) $user->media_count),
                'size' => Helpers::humanizeBytes((int) ($user->storage_used ?? 0)),
            ])
            ->all();
    }

    public function render(): object
    {
        return view('livewire.admin.settings')->title('Settings');
    }
}
