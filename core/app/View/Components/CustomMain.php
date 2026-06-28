<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CustomMain extends Component
{
    public function __construct(

        // Slots
        public mixed $sidebar = null,
        public mixed $content = null,
        public mixed $footer = null,
        public ?bool $fullWidth = false,
        public ?bool $withNav = false,
        public ?string $collapseText = 'Collapse',
        public ?string $collapseIcon = 'o-bars-3-bottom-right',
        public ?bool $collapsible = false,
    ) {
    }

    public function render(): View|Closure|string
    {
        return <<<'BLADE'
                 <main id="main" @class(["w-full mx-auto flex-grow", "max-w-screen-2xl" => !$fullWidth])>
                    <div @class([
                        "drawer lg:drawer-open",
                        "drawer-end" => $sidebar?->attributes['right'],
                        "max-sm:drawer-end" => $sidebar?->attributes['right-mobile'],
                    ])>
                        <input id="{{ $sidebar?->attributes['drawer'] }}" type="checkbox" class="drawer-toggle" />
                        <div {{ $content->attributes->class(["drawer-content w-full mx-auto p-5 lg:px-10 lg:py-5"]) }}>
                            {{-- MAIN CONTENT  --}}
                            {{ $content }}
                        </div>
                    </div>
                </main>

                 {{-- FOOTER  --}}
                 @if($footer)
                    <footer {{ $footer?->attributes->class(["mx-auto w-full", "max-w-screen-2xl" => !$fullWidth ]) }}>
                        {{ $footer }}
                    </footer>
                @endif
                BLADE;
    }
}
