<?php

use Illuminate\Http\Request;
use PureDevLabs\ProxyDownload;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function ()
{
    return view('welcome');
})->name('index');

Route::get('proxyDownload', function (Request $request)
{
    if (!empty($request->url)) ProxyDownload::chunkedDownload($request->url);
});

Route::middleware(['auth:sanctum', 'verified'])->group(function ()
{
    Route::prefix('admin')->group(function ()
    {
        Route::get('/', function ()
        {
            return redirect()->route('dashboard');
        });

        Route::get('/dashboard', function ()
        {
            return view('dashboard');
        })->name('dashboard');

        Route::get('/settings', function ()
        {
            return view('admin.settings');
        })->name('settings');

        Route::get('/cookies', function ()
        {
            return view('admin.cookie-management');
        })->name('cookies');

        Route::get('/dmca', function ()
        {
            return view('admin.url-blacklist');
        })->name('dmca');

        Route::get('/apimanagement', function ()
        {
            return view('admin.api-management');
        })->name('apimanagement');

        Route::get('/managekey/{id}', function ($id)
        {
            return view('admin.manage-key', ['id' => $id]);
        })->name('managekey');
    });
});
