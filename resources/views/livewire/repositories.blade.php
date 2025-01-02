<div class="p-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">GitHub Repositories</h1>

    <!-- Error Message -->
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

    <!-- Search Input -->
    <div class="mb-6">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            class="w-full border border-gray-300 rounded-lg p-3 shadow-sm focus:ring focus:ring-blue-200"
            placeholder="Search repositories..."
        />
    </div>

    <!-- Loader -->
    <x-loading />

    @if ($repositories->isNotEmpty())
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach ($repositories as $repo)
                @php
                    $full_name_repo = $repo['full_name'];
                    $isFavorite = in_array($full_name_repo, $favoriteRepositories);
                @endphp
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow">
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
                        wire:click="showNotificationModal('{{ $full_name_repo }}')"
                        class="mt-4 px-4 py-2 text-white rounded-lg {{ $isFavorite ? 'bg-red-500' : 'bg-green-500' }}"
                    >
                        {{ $isFavorite ? 'Unfavorite' : 'Favorite' }}
                    </button>
                </div>
            @endforeach
        </div>

        <!-- Pagination Controls -->
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

    <!-- Modal -->
    @if ($showModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg p-6 w-1/3">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Select Notification Method</h2>

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

                <div class="mt-6 flex justify-end">
                    <button
                        wire:click="$set('showModal', false)"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 mr-3"
                    >
                        Cancel
                    </button>
                    <button
                        wire:click="toggleFavorite"
                        class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600"
                    >
                        Save
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
