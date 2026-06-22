<?php

namespace App\Livewire;

use App\Models\Properties\ResourceType;
use App\Models\Resource;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Mary\Traits\Toast;

class Preview extends Component
{
    use Toast;

    /**
     * Largest text file rendered inline; larger files only offer a download.
     */
    private const MAX_TEXT_PREVIEW_BYTES = 1024 * 1024;

    public Resource $resource;

    public function mount(Resource $resource, ?string $ext = null): void
    {
        view()->share('previewMode', true);
        $this->resource = $resource;

        if (! $resource->isAccessibleBy(auth()->user())) {
            abort(404);
        }

        if ($ext && $resource->extension !== $ext) {
            abort(404);
        }
    }

    /**
     * Whether the resource is too large to render its text inline; such files
     * only offer a download. Displayability is already gated by the view via
     * {@see ResourceType::isDisplayable()}.
     */
    #[Computed]
    public function textTooLarge(): bool
    {
        return ($this->resource->size ?? 0) > self::MAX_TEXT_PREVIEW_BYTES;
    }

    /**
     * The textual content of the resource, read from storage.
     */
    #[Computed]
    public function textContent(): string
    {
        return Storage::get($this->resource->storage_path) ?? '';
    }

    public function render()
    {
        return view('livewire.preview')->title($this->resource->filename ?? $this->resource->code);
    }
}
