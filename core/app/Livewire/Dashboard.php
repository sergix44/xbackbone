<?php

namespace App\Livewire;

use App\Actions\Resource\DeleteResource;
use App\Actions\Resource\ListResources;
use App\Actions\Resource\StoreResource;
use App\Actions\Resource\ToggleResourceVisibility;
use App\Actions\Resource\UpdateResourceSettings;
use App\Models\Resource;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class Dashboard extends Component
{
    use Toast, WithFileUploads, WithPagination;

    public bool $showUploadDrawer = false;

    public array $files = [];

    public string $linkUrl = '';

    public string $linkName = '';

    public bool $confirmingDelete = false;

    public ?int $deletingId = null;

    public bool $showSettingsModal = false;

    public ?int $settingsId = null;

    public ?string $settingsPassword = null;

    public bool $settingsRemovePassword = false;

    public ?string $settingsExpiresAt = null;

    public bool $settingsHasPassword = false;

    /* LISTING — persisted in the query string so filters survive a refresh/share. */
    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 'created_at')]
    public string $sortColumn = 'created_at';

    #[Url(except: 'desc')]
    public string $sortDirection = 'desc';

    public function render()
    {
        return view('livewire.dashboard')->title('Gallery');
    }

    #[Computed]
    public function resources()
    {
        // The Livewire update request carries the component snapshot, not query
        // params; merge the listing state into it so ListResources resolves the
        // same `filter`/`sort` vocabulary the REST API uses.
        request()->merge([
            'filter' => array_filter(['search' => $this->search], fn ($value) => $value !== ''),
            'sort' => ($this->sortDirection === 'desc' ? '-' : '').$this->sortColumn,
        ]);

        return app(ListResources::class)(auth()->user());
    }

    /**
     * Columns offered in the "Sort by" dropdown, mapped to their display labels.
     * Keys must match the allowed sorts in ListResources.
     *
     * @return array<string, string>
     */
    public function sortLabels(): array
    {
        return [
            'created_at' => __('Date'),
            'name' => __('Name'),
            'size' => __('Size'),
            'views' => __('Views'),
            'downloads' => __('Downloads'),
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function setSort(string $column): void
    {
        if (! array_key_exists($column, $this->sortLabels())) {
            return;
        }

        $this->sortColumn = $column;
        $this->resetPage();
    }

    public function toggleSortDirection(): void
    {
        $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        $this->resetPage();
    }

    public function saveUpload(int $id): void
    {
        /** @var TemporaryUploadedFile|null $file */
        $file = $this->files[$id] ?? null;

        if (! $file) {
            $this->error('File not found');

            return;
        }

        $resource = app(StoreResource::class)(auth()->user(), $file);

        activity()
            ->performedOn($resource)
            ->causedBy(auth()->user())
            ->log('resource.uploaded');

        $this->success('Upload successful!', $resource->preview_ext_url);

        $file->delete();
    }

    public function createPaste(string $content, ?string $name = null): void
    {
        $validated = validator(
            ['content' => $content, 'name' => $name],
            [
                'content' => ['required', 'string', 'max:1048576'],
                'name' => ['nullable', 'string', 'max:255'],
            ]
        )->validate();

        $resource = app(StoreResource::class)(
            auth()->user(),
            data: $validated['content'],
            name: ($validated['name'] ?? null) ?: null,
            mime: 'text/plain',
        );

        activity()
            ->performedOn($resource)
            ->causedBy(auth()->user())
            ->log('resource.uploaded');

        $this->showUploadDrawer = false;

        unset($this->resources);

        $this->success('Paste created!', $resource->preview_ext_url);
    }

    public function createLink(): void
    {
        $validated = $this->validate([
            'linkUrl' => ['required', 'url:http,https', 'max:2048'],
            'linkName' => ['nullable', 'string', 'max:255'],
        ]);

        $resource = app(StoreResource::class)(
            auth()->user(),
            data: $validated['linkUrl'],
            name: $validated['linkName'] ?: null,
        );

        activity()
            ->performedOn($resource)
            ->causedBy(auth()->user())
            ->log('resource.uploaded');

        $this->reset('linkUrl', 'linkName');
        $this->showUploadDrawer = false;

        unset($this->resources);

        $this->success('Link created!', $resource->preview_ext_url);
    }

    public function toggleVisibility(int $id): void
    {
        $resource = Resource::query()->find($id);

        if (! $resource || $resource->user_id !== auth()->id()) {
            $this->error('Resource not found');

            return;
        }

        app(ToggleResourceVisibility::class)($resource);

        activity()
            ->performedOn($resource)
            ->causedBy(auth()->user())
            ->log($resource->is_private ? 'resource.hidden' : 'resource.published');

        unset($this->resources);

        $this->success($resource->is_private ? 'Resource hidden' : 'Resource published');
    }

    public function editSettings(int $id): void
    {
        $resource = Resource::query()->find($id);

        if (! $resource || $resource->user_id !== auth()->id()) {
            $this->error('Resource not found');

            return;
        }

        $this->settingsId = $resource->id;
        $this->settingsExpiresAt = $resource->expires_at?->format('Y-m-d\TH:i');
        $this->settingsHasPassword = $resource->hasPassword();
        $this->settingsPassword = null;
        $this->settingsRemovePassword = false;

        $this->resetValidation();
        $this->showSettingsModal = true;
    }

    public function saveSettings(UpdateResourceSettings $updateResourceSettings): void
    {
        // Treat empty inputs as "no value": null skips the nullable rules and, for
        // the password, signals "keep the current one".
        $this->settingsExpiresAt = $this->settingsExpiresAt ?: null;
        $this->settingsPassword = $this->settingsPassword ?: null;

        $this->validate([
            'settingsExpiresAt' => ['nullable', 'date', 'after:now'],
            'settingsPassword' => ['nullable', 'string', 'min:4', 'max:255'],
        ]);

        $resource = Resource::query()->find($this->settingsId);

        if (! $resource || $resource->user_id !== auth()->id()) {
            $this->error('Resource not found');

            return;
        }

        $updateResourceSettings($resource, [
            'expires_at' => $this->settingsExpiresAt,
            'password' => $this->settingsPassword,
            'remove_password' => $this->settingsRemovePassword,
        ]);

        activity()
            ->performedOn($resource)
            ->causedBy(auth()->user())
            ->log('resource.updated');

        $this->showSettingsModal = false;

        unset($this->resources);

        $this->success('Settings updated');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->confirmingDelete = true;
    }

    public function deleteResource(): void
    {
        $resource = Resource::query()->find($this->deletingId);

        if (! $resource || $resource->user_id !== auth()->id()) {
            $this->error('Resource not found');

            return;
        }

        app(DeleteResource::class)($resource);

        activity()
            ->performedOn($resource)
            ->causedBy(auth()->user())
            ->log('resource.deleted');

        $this->confirmingDelete = false;
        $this->deletingId = null;

        unset($this->resources);

        $this->success('Resource deleted');
    }
}
