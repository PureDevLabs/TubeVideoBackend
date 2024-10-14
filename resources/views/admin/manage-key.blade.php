<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Manage API Key') }}
        </h2>
    </x-slot>

    <div class="pt-12" style="padding-top: 48px">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden">
                @livewire('key-settings', ['keyId' => $id])
            </div>
        </div>
    </div>

    <div class="pb-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden">
                @livewire('manage-key', ['keyId' => $id])
            </div>
        </div>
    </div>
</x-app-layout>
