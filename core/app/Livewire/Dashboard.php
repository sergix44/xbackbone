<?php

namespace App\Livewire;

use App\Actions\Resource\DeleteResource;
use App\Actions\Resource\ListResources;
use App\Actions\Resource\StoreResource;
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
        /** @var TemporaryUploadedFile $file */
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
