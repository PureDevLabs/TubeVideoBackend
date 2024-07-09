<x-guest-layout>
    <div class="min-h-screen bg-base-200">
        <div class="mx-auto text-center">
            <div class="max-w-2xl mx-auto">
                <h1 class="text-2xl md:text-5xl font-bold py-4">MP3 Converter Pro Installer</h1>
                @if (!$data['status'])
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            @isset($data['error'])
                            <div class="alert alert-error shadow-lg">
                                <div>
                                  <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                  <span>{{ $data['errorMsg'] }}</span>
                                </div>
                              </div>
                            @endisset
                            <h2 class="card-title text-2xl">Create Config</h2>
                            <form action="{{ route('saveConfig') }}" method="post">
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text text-xl">Database Settings</span>
                                    </label>
                                    <label class="input-group my-2">
                                        <span class="w-20">Host</span>
                                        <input type="text" name="hostname" placeholder="127.0.0.1" value="127.0.0.1" class="input input-bordered w-full">
                                    </label>
                                    <label class="input-group my-2">
                                        <span class="w-20">Name</span>
                                        <input type="text" name="database" placeholder="Database Name" class="input input-bordered w-full">
                                    </label>
                                    <label class="input-group my-2">
                                        <span class="w-20">User</span>
                                        <input type="text" name="username" placeholder="Database User" class="input input-bordered w-full">
                                    </label>
                                    <label class="input-group my-2">
                                        <span>Password</span>
                                        <input type="text" name="password" placeholder="Database Password" class="input input-bordered w-full">
                                    </label>
                                </div>

                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text text-xl">Redis Settings</span>
                                    </label>
                                    <label class="input-group my-2">
                                        <span class="w-20">Host</span>
                                        <input type="text" name="redis_host" placeholder="127.0.0.1" value="127.0.0.1" class="input input-bordered w-full">
                                    </label>
                                    <label class="input-group my-2">
                                        <span class="w-20">Port</span>
                                        <input type="text" name="redis_port" placeholder="Redis Port" value="6379" class="input input-bordered w-full">
                                    </label>
                                    <label class="input-group my-2">
                                        <span>Password</span>
                                        <input type="text" name="redis_password" placeholder="Redis Password" value="null" class="input input-bordered w-full">
                                    </label>
                                </div>
                                <div class="card-actions justify-end">
                                    <button type="submit" class="btn btn-primary">Create Config</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-guest-layout>
