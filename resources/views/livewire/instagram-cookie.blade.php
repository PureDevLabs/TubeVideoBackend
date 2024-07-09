<div>
    <div>
        <x-jet-form-section submit="UpdateInstagramCookie">
            <x-slot name="title">
                {{ __('Instagram Login Cookie') }}
            </x-slot>

            <x-slot name="description">
                <p class="text-xl">Download our Instagram Cookie Extractor Software for
                    <a class="font-bold text-red-500 underline" href="#" target="_blank">Windows x64 Desktop</a>
                to easily generate Instagram Login Cookie.
                </p>
            </x-slot>

            <x-slot name="form">
                <!-- License Local Key -->
                <div class="col-span-6 sm:col-span-6">
                    <x-jet-label for="instaCookie" value="{{ __('Instagram Cookie') }}" />
                    <div class="form-control">
                        <textarea id="instaCookie" wire:model.defer="state.instaCookie"
                            class="textarea h-24 textarea-bordered" placeholder="Paste only a NETSCAPE format cookie here."></textarea>
                    </div>
                </div>
            </x-slot>

            <x-slot name="actions">
                <x-jet-action-message class="mr-3" on="saved">
                    {{ __('Saved.') }}
                </x-jet-action-message>

                <x-jet-button wire:loading.attr="disabled">
                    {{ __('Save') }}
                </x-jet-button>
            </x-slot>
        </x-jet-form-section>
    </div>

</div>
