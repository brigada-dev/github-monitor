<div class="p-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">GitHub Repositories</h1>

    <!-- Error Message -->
    @if ($errorMessage)
        <div class="bg-red-100 text-red-800 p-3 rounded mb-6">
            {{ $errorMessage }}
        </div>
    @endif

    <!-- Search Input -->
    <div class="mb-6">
        <input
            type="text"
            wire:model.debounce.300ms="search"
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
                    $full_name_repo = explode('/', $repo['full_name']);
                @endphp
                <div
                    class="bg-gray-50 border border-gray-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow">
                    <a
                        href="{{ route('commits', ['owner' => $full_name_repo[0], 'repo_name' => $full_name_repo[1]]) }}"
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
</div>
