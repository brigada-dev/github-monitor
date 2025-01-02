<div class="p-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Commits for Repository: {{ $repoId }}</h1>

    <!-- Error Message -->
    @if ($errorMessage)
        <div class="bg-red-100 text-red-800 p-3 rounded mb-6">
            {{ $errorMessage }}
        </div>
    @endif

    <!-- Branch Selector -->
    <div class="flex items-center gap-4 flex-col lg:flex-row">
        <div class="mb-6 w-full">
            <label for="branch" class="block text-sm font-medium text-gray-700 mb-2">Select Branch:</label>
            <select id="branch" wire:model.live="branch" class="w-full border-gray-300 rounded-lg p-3">
                <option value="">All Branches</option>
                @foreach ($branches as $branchOption)
                    <option value="{{ $branchOption['name'] }}">{{ $branchOption['name'] }}</option>
                @endforeach
            </select>
        </div>

        <!-- Date Selector -->
        <div class="mb-6 w-full">
            <label for="filterDate" class="block text-sm font-medium text-gray-700 mb-2">Select Date:</label>
            <input
                type="date"
                id="filterDate"
                wire:model.live="filterDate"
                max="{{ Carbon\Carbon::now()->toDateString() }}"
                class="w-full border-gray-300 rounded-lg p-3"
            />
        </div>
    </div>

    <!-- Contributors -->
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Commits on {{ $filterDate }}</h2>
        <x-loading />
        @if (!empty($contributors))
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach ($contributors as $contributor)
                    <div class="bg-gray-100 border border-gray-200 rounded-lg p-4 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-700">{{ $contributor['author'] }}</h3>
                        <p class="text-sm text-gray-600 mt-2">Commits: <span class="font-bold">{{ $contributor['commit_count'] }}</span></p>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 text-center mt-6">No commits found for the selected date.</p>
        @endif
    </div>
</div>
