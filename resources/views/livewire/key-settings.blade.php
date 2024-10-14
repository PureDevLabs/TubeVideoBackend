<div>
    <!-- Configure Key Settings -->
    <x-jet-form-section submit="update">
        <x-slot name="title">
            {{ __('Configure Key Settings') }}
        </x-slot>

        <x-slot name="description">
            {{ __('Change how and which download URLs are included in the API response.') }}
        </x-slot>

        <x-slot name="form">
            <!-- Max Video Duration -->
            <div class="col-span-6 sm:col-span-4">
                <x-jet-label for="max_video_duration" value="{{ __('Maximum allowed video duration (in seconds)') }}" />
                <x-jet-input id="max_video_duration" type="text" class="mt-1 block w-full" wire:model.defer="state.max_video_duration" />
                <x-jet-input-error for="max_video_duration" class="mt-2" />
            </div>
        </x-slot>

        <x-slot name="actions">
            <x-jet-action-message class="mr-3" on="saved">
                {{ __('Saved.') }}
            </x-jet-action-message>

            <x-jet-button>
                {{ __('Save') }}
            </x-jet-button>
        </x-slot>
    </x-jet-form-section>

    <x-jet-section-border />

</div>
