<?php

namespace XBB\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AppBrand extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(public ?bool $onTop = false)
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return <<<'HTML'
                <a href="/" wire:navigate>
                    <!-- Hidden when collapsed -->
                    <div {{ $attributes->class(["hidden-when-collapsed"]) }}>
                        <div class="flex items-center {{ $onTop ? 'flex-col justify-center' : 'gap-2 flex-nowrap' }}">
                            <div class="avatar">
                                <div class="{{ $onTop ? 'w-24' : 'w-12' }}">
                                    <img src="{{ asset('img/android-chrome-192x192.png') }}"  alt="Application Logo">
                              </div>
                            </div>
                            <span class="font-bold text-3xl bg-gradient-to-r from-primary to-accent bg-clip-text text-transparent ">
                                {{ config('app.name') }}
                            </span>
                        </div>
                    </div>

                    <!-- Display when collapsed -->
                    <div class="display-when-collapsed hidden mx-5 mt-4 lg:mb-6 h-[28px]">
                        <x-icon name="s-square-3-stack-3d" class="w-6 -mb-1 text-purple-500" />
                    </div>
                </a>
            HTML;
    }
}
