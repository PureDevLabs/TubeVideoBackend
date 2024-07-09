<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 sm:px-20 bg-white border-b border-gray-200">
                    <div class="mt-8 text-2xl">
                        Welcome to your Tube Video Backend application!
                    </div>
                    <div class="mt-8 text-xl">

                        @if ((int) \PureDevLabs\BackendApp::Version()['backend'])
                            @if (\PureDevLabs\BackendApp::Version()['app'] === \PureDevLabs\BackendApp::Version()['backend'])
                                <div class="card lg:card-side card-bordered">

                                    <div class="py-0 card-body">
                                        <h2 class="card-title">Software Update Checker</h2>
                                        <p class="text-gray-500">Current Version:
                                            {{ \PureDevLabs\BackendApp::Version()['backend'] }}</p>
                                        <p class="text-gray-500">Your Version:
                                            {{ \PureDevLabs\BackendApp::Version()['app'] }}</p>
                                    </div>
                                </div>
                            @else
                                <div class="card lg:card-side card-bordered">
                                    <div class="py-0 card-body">
                                        <h2 class="card-title">An update is available</h2>
                                        <p class="text-gray-500">Current Version:
                                            {{ \PureDevLabs\BackendApp::Version()['backend'] }}</p>
                                        <p class="text-gray-500">Your Version:
                                            {{ \PureDevLabs\BackendApp::Version()['app'] }}</p>
                                        <div class="card-actions">
                                            <a href="https://github.com/PureDevLabs/TubeVideoBackend" target="_blank"
                                                class="btn btn-primary">Download the latest
                                                version here</a>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @else
                            <div class="card lg:card-side card-bordered">
                                <div class="py-0 card-body">
                                    <h2 class="card-title">Couldn't check Software version</h2>
                                    <p class="text-gray-500">Current Version:
                                        {{ \PureDevLabs\BackendApp::Version()['backend'] }}</p>
                                    <p class="text-gray-500">Your Version:
                                        {{ \PureDevLabs\BackendApp::Version()['app'] }}</p>
                                    <p class="text-gray-500">Contact support if it keeps fail.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
