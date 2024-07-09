<div>
    <x-jet-form-section submit="UpdateLicenseInformation">
        <x-slot name="title">
            {{ __('License Settings') }}
        </x-slot>

        <x-slot name="description">
            {{ __('Update your Product License information here.') }}
        </x-slot>

        <x-slot name="form">
            <!-- Product License key -->
            <div class="col-span-6 sm:col-span-6">
                <x-jet-label for="productKey" value="{{ __('Product License Key') }}" />
                <x-jet-input id="productKey" type="text" class="mt-1 block w-full" wire:model.defer="state.productKey" />
                <x-jet-input-error for="productKey" class="mt-2" />
            </div>
            <!-- License Local Key -->
            <div class="col-span-6 sm:col-span-6">
                <x-jet-label for="localKey" value="{{ __('License Local Key') }}" />
                <div class="form-control">
                    <textarea id="localKey" wire:model.defer="state.localKey"
                        class="textarea h-24 textarea-bordered" placeholder="Leave it empty, and it will get automatically filled." @if(empty($state['localKey'])) disabled @endif></textarea>
                </div>
            </div>
        </x-slot>

        <x-slot name="actions">
            <x-jet-action-message class="mr-3" on="saved">
                {{ __('Saved.') }}
            </x-jet-action-message>

            <x-jet-button wire:loading.attr="disabled" wire:target="photo">
                {{ __('Save') }}
            </x-jet-button>
        </x-slot>
    </x-jet-form-section>
</div>
