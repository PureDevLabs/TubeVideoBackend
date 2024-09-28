<div>
    <?php /*
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
    */ ?>
    <x-jet-form-section submit="UpdateAuthMethod">
        <x-slot name="title">
            {{ __('YouTube Authentication') }}
        </x-slot>

        <x-slot name="description">
            {{ __('Choose method of YouTube authentication.') }}
        </x-slot>

        <x-slot name="form">
            <div class="col-span-6 sm:col-span-6">
                <div class="p-6 card bordered">
                    <div class="form-control">
                        <label class="label cursor-pointer">
                          <span class="label-text">None <i>(No authentication; recommended setting for HTTP proxy use)</i></span>
                          <input type="radio" id="authMethod" name="radio-6" class="radio checked:bg-red-500" value="" wire:model.defer="state.authMethod">
                        </label>
                    </div>
                    <div class="form-control">
                        <label class="label cursor-pointer">
                          <span class="label-text">OAuth Tokens <i>(Manually create via "youtube-oauth" Bun script and add to Backend admin)</i></span>
                          <input type="radio" id="authMethod" name="radio-6" class="radio checked:bg-blue-500" value="oauth" wire:model.defer="state.authMethod">
                        </label>
                    </div>
                    <div class="form-control">
                        <label class="label cursor-pointer">
                            <span class="label-text">Trusted Session <i>(Automatically generate via "youtube-trusted-session" Bun script in <b>/home</b> directory)</i></span>
                            <input type="radio" id="authMethod" name="radio-6" class="radio checked:bg-blue-500" value="session" wire:model.defer="state.authMethod">
                        </label>
                    </div>
                </div>
            </div>
        </x-slot>

        <x-slot name="actions">
            <x-jet-action-message class="mr-3" on="saved">
                {{ __('Saved.') }}
            </x-jet-action-message>

            <x-jet-button wire:loading.attr="disabled" wire:target="photo" onclick="document.querySelector('#oauthNav').style.display = (document.querySelector('input[name=radio-6]:checked').value == 'oauth') ? 'inline-flex' : 'none';">
                {{ __('Save') }}
            </x-jet-button>
        </x-slot>
    </x-jet-form-section>
</div>
