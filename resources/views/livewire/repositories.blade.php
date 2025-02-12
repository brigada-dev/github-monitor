<div class="lg:p-6 p-4">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">GitHub Repositories</h1>
    @if ($errorMessage)
        <div class="bg-red-100 text-red-800 p-3 rounded mb-6">
            {{ $errorMessage }}
            @if (!$githubToken)
                <form wire:submit.prevent="setGitHubToken">
                    <input
                        type="text"
                        wire:model.defer="githubToken"
                        placeholder="Enter your GitHub token"
                        class="w-full border border-gray-300 rounded-lg p-3 shadow-sm focus:ring focus:ring-blue-200 mb-4"
                        required
                    />
                    <button
                        type="submit"
                        class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600"
                    >
                        Save Token
                    </button>
                </form>
            @endif
        </div>
    @endif
    <div class="mb-6">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            class="w-full border border-gray-300 rounded-lg p-3 shadow-sm focus:ring focus:ring-blue-200"
            placeholder="Search repositories..."
        />
    </div>
    <x-loading wire:except="showNotificationModal"/>
    @if ($repositories->isNotEmpty())
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach ($repositories as $repo)
                @php
                    $full_name_repo = $repo['full_name'];
                    $isFavorite = in_array($full_name_repo, $favoriteRepositories);
                @endphp
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between">
                    <a
                        href="{{ route('commits', ['owner' => explode('/', $full_name_repo)[0], 'repo_name' => explode('/', $full_name_repo)[1]]) }}"
                        class="text-blue-600 text-lg font-semibold hover:underline"
                    >
                        {{ $repo['name'] }}
                    </a>
                    <p class="text-gray-600 mt-2">
                        {{ $repo['description'] ?? 'No description available' }}
                    </p>
                    <div class="text-sm text-gray-500 mt-2">
                        <span>🌟 {{ $repo['stargazers_count'] ?? 0 }} Stars</span>
                        <span class="ml-4">🍴 {{ $repo['forks_count'] ?? 0 }} Forks</span>
                    </div>
                    <button
                        wire:click="{{ \App\Models\FavoriteRepository::where('repository_name', $full_name_repo)->where('user_id', auth()->id())->exists() ? "toggleFavorite('$full_name_repo')" : "showNotificationModal('$full_name_repo')" }}"
                        class="mt-4 px-4 py-2 text-white rounded-lg {{ $isFavorite ? 'bg-red-500' : 'bg-green-500' }}"
                    >
                        {{ $isFavorite ? 'Unfavorite' : 'Favorite' }}
                    </button>
                </div>
            @endforeach
        </div>
        <div class="mt-8 flex justify-between items-center">
            <button
                wire:click="previousPage"
                @if ($page === 1) disabled @endif
                class="px-6 py-2 border rounded-lg bg-gray-200 text-gray-600 hover:bg-gray-300 disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed"
            >
                Previous
            </button>

            <button
                wire:click="nextPage"
                @if ($isLastPage) disabled @endif
                class="px-6 py-2 border rounded-lg bg-blue-500 text-white hover:bg-blue-600 disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed"
            >
                Next
            </button>
        </div>
    @else
        <div class="text-center py-6">
            <p class="text-gray-500">No repositories found. Try a different search term.</p>
        </div>
    @endif

    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50" wire:click.self="$set('showModal', false)">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6 relative">
                <!-- Modal Header -->
                <div class="flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-800">Notification Settings</h2>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <!-- Modal Body -->
                <div class="mt-4">
                    <!-- Notification Method -->
                    <label for="notificationMethod" class="block text-sm font-medium text-gray-700 mb-2">Notification Method</label>
                    <select
                        id="notificationMethod"
                        wire:model="notificationMethod"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    >
                        @foreach ($notificationOptions as $option)
                            <option value="{{ $option }}">{{ ucfirst($option) }}</option>
                        @endforeach
                    </select>
                    <!-- Notification Trigger -->
                    <div class="mt-4">
                        <label for="notificationTrigger" class="block text-sm font-medium text-gray-700">
                            {{ ucfirst($notificationMethod) }} Address/URL
                        </label>
                        <input
                            id="notificationTrigger"
                            type="text"
                            wire:model.defer="notificationTrigger"
                            class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            placeholder="Enter your {{ $notificationMethod }} address or URL"
                        />
                        @error('notificationTrigger')
                        <span class="text-red-600 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <!-- Select Branches -->
                    <div class="mt-4">
                        <label for="branches" class="block text-sm font-medium text-gray-700">Select Branches</label>
                        <div
                            class="relative"
                            x-data="{ open: @entangle('showBranchesDropdown') }" {{-- Alpine.js for dropdown toggle --}}
                            x-on:click.outside="open = false" {{-- Close when clicking outside --}}
                        >
                            <button
                                type="button"
                                class="w-full bg-white flex justify-between items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                x-on:click="open = !open" {{-- Toggle dropdown --}}
                            >
                                <span>{{ count($selectedBranches) > 0 ? implode(', ', $selectedBranches) : 'Select branches...' }}</span>
                                <svg class="w-5 h-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div
                                x-show="open"
                                class="absolute z-10 mt-2 bg-white border border-gray-300 rounded-lg shadow-lg max-h-48 overflow-auto w-full"
                                style="display: none;" {{-- Hide initially --}}
                            >
                                <ul class="py-1 text-sm text-gray-700">
                                    @foreach ($availableBranches as $branch)
                                        <li
                                            class="px-4 py-2 cursor-pointer hover:bg-indigo-100 flex items-center"
                                            wire:click="toggleBranch('{{ $branch }}')" {{-- Select branch when clicking on li --}}
                                        >
                                            <input
                                                type="checkbox"
                                                value="{{ $branch }}"
                                                wire:model="selectedBranches"
                                                class="mr-2 text-indigo-500 focus:ring-indigo-500 border-gray-300 rounded pointer-events-none"
                                            />
                                            <span>{{ $branch }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        @error('selectedBranches')
                        <span class="text-red-600 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <!-- Modal Footer -->
                <div class="mt-6 flex justify-end">
                    <button wire:click="$set('showModal', false)" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 mr-3">
                        Cancel
                    </button>
                    <button wire:click="toggleFavorite" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                        Save
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
