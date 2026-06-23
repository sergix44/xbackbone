<?php

namespace App\Livewire;

use App\Actions\Resource\DeleteResource;
use App\Actions\Resource\ListResources;
use App\Actions\Resource\StoreResource;
use App\Actions\Resource\ToggleResourceVisibility;
use App\Models\Resource;
use Livewire\Attributes\Computed;
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

    public function render()
    {
        return view('livewire.dashboard')->title('Gallery');
    }

    #[Computed]
    public function resources()
    {
        return app(ListResources::class)(auth()->user());
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
