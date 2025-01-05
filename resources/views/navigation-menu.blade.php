<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu (desktop + tablet) -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Left side (Logo + Nav) -->
            <div class="flex items-center space-x-8">
                <!-- Logo (Text-based) -->
                <div class="shrink-0">
                    <a
                        href="{{ route('repositories') }}"
                        class="text-xl font-bold text-gray-700 hover:text-gray-900"
                    >
                        Github-Monitor
                    </a>
                </div>

                <!-- Main Nav Links (hidden on mobile, shown on sm+) -->
                <div class="hidden sm:flex sm:items-center sm:space-x-6">
                    <!-- Dashboard link -->
                    <x-nav-link href="{{ route('repositories') }}" :active="request()->routeIs('repositories')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    <!-- Repositories Dropdown (Desktop) -->
                    <div
                        x-data="{ openDropdown: false }"
                        class="relative text-left"
                        @click.outside="openDropdown = false"
                    >
                        <button
                            @click="openDropdown = !openDropdown"
                            type="button"
                            class="inline-flex items-center rounded-md border border-gray-300
                                   bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm
                                   hover:bg-gray-50"
                        >
                            Repositories
                            <svg
                                class="ml-2 h-5 w-5"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M5.23 7.21a.75.75 0 011.06.02L10 10.584l3.71-3.354a.75.75 0 111.04 1.08l-4.25 3.84a.75.75 0 01-1.04 0L5.23 8.27a.75.75 0 01.02-1.06z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </button>
                        <!-- Desktop Dropdown Panel -->
                        <div
                            class="absolute right-0 mt-2 w-48 origin-top-right rounded-md bg-white
                                   shadow-lg ring-1 ring-black ring-opacity-5 hidden"
                            :class="{ 'block': openDropdown, 'hidden': !openDropdown }"
                        >
                            <div class="py-1">
                                @foreach(\App\Models\FavoriteRepository::where('user_id', auth()->id())->get(['id','repository_name']) as $repo)
                                    @php
                                        $full_name_repo = $repo->repository_name;
                                        $owner = explode('/', $full_name_repo)[0] ?? '';
                                        $repoName = explode('/', $full_name_repo)[1] ?? '';
                                    @endphp

                                    <x-nav-link
                                        href="{{ route('commits', ['owner' => $owner, 'repo_name' => $repoName]) }}"
                                        :active="request()->routeIs(route('commits', ['owner' => $owner, 'repo_name' => $repoName]))"
                                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                    >
                                        {{ $full_name_repo }}
                                    </x-nav-link>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right side (Settings Dropdown + Hamburger) -->
            <div class="hidden sm:flex sm:items-center sm:space-x-4">
                <!-- Settings Dropdown (Jetstream style) -->
                <div class="relative">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <span class="inline-flex rounded-md">
                                <button
                                    type="button"
                                    class="inline-flex items-center px-3 py-2 border border-transparent
                                           text-sm leading-4 font-medium rounded-md text-gray-500 bg-white
                                           hover:text-gray-700 focus:outline-none focus:bg-gray-50
                                           active:bg-gray-50 transition ease-in-out duration-150"
                                >
                                    {{ Auth::user()->name }}
                                    <svg
                                        class="ms-2 -me-0.5 h-4 w-4"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.5"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M19.5 8.25l-7.5 7.5-7.5-7.5"
                                        />
                                    </svg>
                                </button>
                            </span>
                        </x-slot>

                        <x-slot name="content">
                            <!-- Account Management -->
                            <div class="block px-4 py-2 text-xs text-gray-400">
                                {{ __('Manage Account') }}
                            </div>

                            <x-dropdown-link href="{{ route('profile.show') }}">
                                {{ __('Profile') }}
                            </x-dropdown-link>

                            <div class="border-t border-gray-200"></div>

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}" x-data>
                                @csrf
                                <x-dropdown-link
                                    href="{{ route('logout') }}"
                                    @click.prevent="$root.submit();"
                                >
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>
            </div>

            <!-- Hamburger (mobile) -->
            <div class="-me-2 flex items-center sm:hidden">
                <button
                    @click="open = !open"
                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-400
                           hover:text-gray-500 hover:bg-gray-100 focus:outline-none
                           focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out"
                >
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <!-- Hamburger icon -->
                        <path
                            :class="{ 'hidden': open, 'inline-flex': !open }"
                            class="inline-flex"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"
                        />
                        <!-- X icon -->
                        <path
                            :class="{ 'hidden': !open, 'inline-flex': open }"
                            class="hidden"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu (hidden by default) -->
    <div :class="{ 'block': open, 'hidden': !open }" class="hidden sm:hidden">
        <!-- Responsive Nav Links -->
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link href="{{ route('repositories') }}" :active="request()->routeIs('repositories')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            <!-- Mobile Repositories Dropdown -->
            <div
                x-data="{ openMobileDropdown: false }"
                @click.outside="openMobileDropdown = false"
                class="border-t border-gray-200 pt-2 pb-3"
            >
                <!-- Toggle button for mobile -->
                <button
                    @click="openMobileDropdown = !openMobileDropdown"
                    class="w-full text-left inline-flex items-center justify-between px-4 py-2 text-base font-medium text-gray-700 hover:bg-gray-100 focus:outline-none"
                >
                    <span>Repositories</span>
                    <!-- Caret icon -->
                    <svg
                        class="h-5 w-5 text-gray-400 transform transition-transform"
                        :class="{ 'rotate-180': openMobileDropdown }"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 011.06.02L10 10.584l3.71-3.354a.75.75 0 111.04 1.08l-4.25 3.84a.75.75 0 01-1.04 0L5.23 8.27a.75.75 0 01.02-1.06z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </button>

                <!-- Mobile dropdown content -->
                <div
                    x-show="openMobileDropdown"
                    x-transition
                    class="mt-1 space-y-1 bg-white"
                >
                    @foreach(\App\Models\FavoriteRepository::where('user_id', auth()->id())->get(['id','repository_name']) as $repo)
                        @php
                            $full_name_repo = $repo->repository_name;
                            $owner = explode('/', $full_name_repo)[0] ?? '';
                            $repoName = explode('/', $full_name_repo)[1] ?? '';
                        @endphp

                        <x-responsive-nav-link
                            href="{{ route('commits', ['owner' => $owner, 'repo_name' => $repoName]) }}"
                            :active="request()->routeIs(route('commits', ['owner' => $owner, 'repo_name' => $repoName]))"
                        >
                            {{ $full_name_repo }}
                        </x-responsive-nav-link>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="flex items-center px-4">
                <div>
                    <div class="font-medium text-base text-gray-800">
                        {{ Auth::user()->name }}
                    </div>
                    <div class="font-medium text-sm text-gray-500">
                        {{ Auth::user()->email }}
                    </div>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <!-- Account Management -->
                <x-responsive-nav-link href="{{ route('profile.show') }}" :active="request()->routeIs('profile.show')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}" x-data>
                    @csrf
                    <x-responsive-nav-link
                        href="{{ route('logout') }}"
                        @click.prevent="$root.submit();"
                    >
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
