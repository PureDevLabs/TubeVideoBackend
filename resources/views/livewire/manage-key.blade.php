<div>
    <!-- Generate API Key -->
    <x-jet-form-section submit="allowIP">
        <x-slot name="title">
            {{ __('Add/Remove IP/Domain') }}
        </x-slot>

        <x-slot name="description">
            {{ __('Add IP Addresses or Domains that are allowed to use this API key. (IPv4 and IPv6 are both supported.)') }}
            <p>Note: For Server Side requests using PHP, NodeJS, Python, etc., allow IP adresses. For Client Side JavaScript, allow Domains.</p>
        </x-slot>

        <x-slot name="form">
            <!-- Token Name -->
            <div class="col-span-6 sm:col-span-4">
                <x-jet-label for="allowed_ip" value="{{ __('IP Address') }}" />
                <x-jet-input id="allowed_ip" type="text" class="mt-1 block w-full" wire:model.defer="allowed_ip" autofocus />
                <x-jet-input-error for="allowed_ip" class="mt-2" />
            </div>
        </x-slot>

        <x-slot name="actions">
            <x-jet-action-message class="mr-3" on="created">
                {{ __('Added.') }}
            </x-jet-action-message>

            <x-jet-button>
                {{ __('Add') }}
            </x-jet-button>
        </x-slot>
    </x-jet-form-section>

    <x-jet-section-border />

    <div class="flex flex-col">
        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    IP
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    API Key
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col" class="relative px-6 py-3">
                                    Action
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($data as $item)
                                @foreach ($item->management as $manage)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ $manage->allowed_ip }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"> {{ $item->apikey }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @empty($manage->allowed_ip)
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                    Inctive
                                                </span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Active
                                                </span>
                                            @endempty
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <x-jet-danger-button wire:click="deleteIP({{ $manage->id }})" onclick="confirm('Are you sure you want to remove this IP address?') || event.stopImmediatePropagation()">
                                                {{ __('Delete') }}
                                            </x-jet-button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
