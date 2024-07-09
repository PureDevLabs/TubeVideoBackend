<?php

use PureDevLabs\Core;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/search', [ApiController::class, 'search']);
Route::post('/related', [ApiController::class, 'related']);
Route::post('/video', [ApiController::class, 'extract']);

Route::post('/ip', function() {
    return app(Core::class)->RefererIP();
});
