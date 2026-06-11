<div class="navbar bg-base-100 w-full mx-auto lg:px-10 shadow p-0">
    <div class="navbar-start">
        @hasSection('menu-items')
            <x-dropdown>
                <x-slot:trigger>
                    <x-button icon="o-bars-3" class="btn btn-ghost btn-sm lg:hidden mr-1"/>
                </x-slot:trigger>
                @yield('menu-items')
            </x-dropdown>
        @elseif(auth()->check())
            <x-dropdown>
                <x-slot:trigger>
                    <x-button icon="o-bars-3" class="btn btn-ghost btn-sm lg:hidden mr-1"/>
                </x-slot:trigger>
                @include('components.navbar.menu-items')
            </x-dropdown>
        @else
        @endif
        <x-app-brand/>
    </div>
    <div class="navbar-center hidden lg:flex">
        @hasSection('menu-items')
            <div class="z-50 flex items-center gap-2">
                @yield('menu-items')
            </div>
        @elseif(auth()->check())
            <x-menu activate-by-route class="menu-horizontal z-50 flex items-center gap-1">
                @include('components.navbar.menu-items')
            </x-menu>
        @else
        @endif
    </div>
    <div class="navbar-end">
        @if($user = auth()->user())
            <x-dropdown right>
                <x-slot:trigger>
                    <div class="btn btn-ghost">
                        <x-avatar :image="$user->avatar" :title="$user->name"/>
                    </div>
                </x-slot:trigger>
                <x-menu-item icon="o-user" title="Profile" link="{{ route('user.profile') }}"/>
                <x-menu-item icon="o-power" title="Quit" link="javascript:document.getElementById('logout-form').submit();" no-wire-navigate/>
            </x-dropdown>
        @endif
    </div>
</div>
