<div>
    <div>
        <x-jet-form-section submit="updateBlacklistURLs">
            <x-slot name="title">
                {{ __('URL Blacklist') }}
            </x-slot>

            <x-slot name="description">
                <p class="text-xl">For each supported site, add video page URLs, one per line, to a list of videos that cannot be downloaded (at the copyright holder's request).</p>
            </x-slot>

            <x-slot name="form">
                <div class="col-span-6 sm:col-span-6">
                    <x-jet-label for="site" value="Supported Site" />
                    <select name="site" class="block mt-1 w-full" wire:model="site" wire:change="change">
                        @foreach($sites as $extractor => $sname)
                            <option value="{{ $extractor }}">{{ $sname }}</option>
                        @endforeach
                    </select>
                    <br>
                    <x-jet-label for="blockedUrls" value="{{ __('Add URLs') }}" />
                    <div class="form-control">
                        <textarea id="blockedUrls" wire:model.defer="blockedUrls"
                            class="textarea h-24 textarea-bordered" placeholder="Add only one URL per line."></textarea>
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
