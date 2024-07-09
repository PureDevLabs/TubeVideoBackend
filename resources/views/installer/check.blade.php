<x-guest-layout :meta="['title' => 'Installer - MP3 Converter Pro v3', 'description' => 'Installer - MP3 Converter Pro v3']">
    <div class="min-h-screen bg-base-200">
        <div class="mx-auto text-center">
            <div class="max-w-2xl mx-auto">
                <h1 class="text-2xl md:text-5xl font-bold py-4">Tube Video Backend Dependencies Checker</h1>
                <div class="overflow-x-auto">
                    <table class="table table-compact md:table-normal w-full text-center">
                        <thead>
                            <tr>
                                <th class="bg-base-300">Dependencies</th>
                                <th class="bg-base-300">Required</th>
                                <th class="bg-base-300">Current</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-left">PHP Version?</td>
                                <td>7.4.x, 8.1.x</td>
                                <td>{!! $data['php_version'] ? '<span class="text-success">' . PHP_VERSION . '</span>' : '<span class="text-error">' . PHP_VERSION . '</span>' !!}</td>
                            </tr>
                            <tr>
                                <td class="text-left">PHP <b>exec()</b> function enabled?</td>
                                <td>yes</td>
                                <td>{!! $data['php_exec'] ? '<span class="text-success">yes</span>' : '<span class="text-error">no</span>' !!}</td>
                            </tr>
                            <tr>
                                <td class="text-left">PHP <b>proc_open()</b> function enabled?</td>
                                <td>yes</td>
                                <td>{!! $data['php_proc_open'] ? '<span class="text-success">yes</span>' : '<span class="text-error">no</span>' !!}</td>
                            </tr>
                            <tr>
                                <td class="text-left">PHP <b>popen()</b> function enabled?</td>
                                <td>yes</td>
                                <td>{!! $data['php_popen'] ? '<span class="text-success">yes</span>' : '<span class="text-error">no</span>' !!}</td>
                            </tr>
                            <tr>
                                <td class="text-left">PHP <b>putenv()</b> function enabled?</td>
                                <td>yes</td>
                                <td>{!! $data['php_putenv'] ? '<span class="text-success">yes</span>' : '<span class="text-error">no</span>' !!}</td>
                            </tr>
                            <tr>
                                <td class="text-left">PHP <b>IonCube</b> Loader installed?</td>
                                <td>yes</td>
                                <td>{!! $data['ioncube_installed'] ? '<span class="text-success">yes</span>' : '<span class="text-error">no</span>' !!}</td>
                            </tr>

                            <tr>
                                <td class="text-left">Redis connection?</td>
                                <td>yes</td>
                                <td>{!! $data['redis_connect'] ? '<span class="text-success">yes</span>' : '<span class="text-error">no</span>' !!}</td>
                            </tr>
                            <tr>
                                <td class="text-left">Database connection?</td>
                                <td>yes</td>
                                <td>{!! $data['check_db'] ? '<span class="text-success">yes</span>' : '<span class="text-error">no</span>' !!}</td>
                            </tr>
                            @if($data['check_db'])
                            <tr>
                                <td class="text-left">Database Table exist?</td>
                                <td>yes</td>
                                <td>{!! $data['check_dbTable'] ? '<span class="text-success">yes</span>' : '<span class="text-error">no</span> <a class="btn btn-sm" href="/installer/migrate">Migrate</>' !!}</td>
                            </tr>
                            @endif

                        </tbody>
                    </table>
                    <a class="btn btn-info btn-outline mt-2" href="{{ route('completeSetup') }}">Complete Setup</a>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
