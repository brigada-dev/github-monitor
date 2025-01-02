<x-form-section submit="saveToken">
    <x-slot name="title">
        {{ __('Update GitHub Token') }}
    </x-slot>

    <x-slot name="description">
        {{ __('Ensure your account is configured with the latest GitHub token for seamless API integration.') }}
    </x-slot>

    <x-slot name="form">



        <div class="col-span-6 sm:col-span-4">
            @if ($successMessage)
                <div class="bg-green-100 text-green-800 p-3 rounded mb-4">
                    {{ $successMessage }}
                </div>
            @endif
            <label for="githubToken" class="block text-sm font-medium text-gray-700">
                {{ __('GitHub Token') }}
            </label>
            <input
                type="text"
                id="githubToken"
                wire:model.defer="githubToken"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                placeholder="{{ __('Enter your GitHub token') }}"
            />
            @error('githubToken')
            <span class="text-sm text-red-600">{{ $message }}</span>
            @enderror
        </div>

    </x-slot>

    <x-slot name="actions">
        <x-action-message class="mr-3" on="saved">
            {{ __('Saved.') }}
        </x-action-message>

        <x-button wire:loading.attr="disabled">
            {{ __('Save') }}
        </x-button>
    </x-slot>
</x-form-section>
