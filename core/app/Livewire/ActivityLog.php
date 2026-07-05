<?php

namespace XBB\Livewire;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;
use XBB\Models\User;

class ActivityLog extends Component
{
    use WithPagination;

    /**
     * When set, the feed is scoped to this user's own activity (profile view);
     * when null it shows every user's activity (admin view). This is only ever
     * honoured for administrators — see {@see scopedCauserId()}.
     */
    public ?int $causerId = null;

    /** The selected event category ('' = all), e.g. 'resource', 'auth'. */
    public string $category = '';

    /** Free-text search over the causer's name/email (global feed only). */
    public string $search = '';

    public function mount(?int $causerId = null): void
    {
        $this->causerId = $causerId;
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * The causer the feed is confined to, or null for the global feed.
     *
     * Public properties are hydrated from the client and therefore untrusted, so
     * the scope is decided here from the authenticated user: only administrators
     * may view the global feed (or another user's), while everyone else is always
     * confined to their own activity regardless of the submitted {@see $causerId}.
     */
    protected function scopedCauserId(): ?int
    {
        $user = auth()->user();

        if ($user?->can('administrate')) {
            return $this->causerId;
        }

        return $user?->getKey();
    }

    /** Whether the feed currently shows every user's activity. */
    public function isGlobal(): bool
    {
        return $this->scopedCauserId() === null;
    }

    /**
     * The paginated activity feed, newest first, honouring the scope and filters.
     */
    #[Computed]
    public function activities(): LengthAwarePaginator
    {
        $causerId = $this->scopedCauserId();

        return Activity::query()
            ->with(['causer', 'subject'])
            ->when($causerId !== null, fn ($query) => $query
                ->where('causer_type', (new User)->getMorphClass())
                ->where('causer_id', $causerId))
            ->when($this->category !== '', fn ($query) => $query->where('description', 'like', $this->category.'.%'))
            ->when($this->isGlobal() && $this->search !== '', function ($query) {
                $term = '%'.$this->search.'%';
                $query->whereHasMorph('causer', [User::class], fn ($inner) => $inner
                    ->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term));
            })
            ->latest()
            ->paginate(15);
    }

    public function render(): object
    {
        return view('livewire.activity-log');
    }
}
