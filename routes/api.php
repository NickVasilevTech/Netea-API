<?php

use App\Http\Controllers\V1\CourseController;
use App\Http\Controllers\V1\UserController;
use Illuminate\Support\Facades\Route;

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

Route::prefix('v1')->group( function (){
    Route::post('user/register', [UserController::class, "register"]);
    Route::post('user/login', [UserController::class, "login"]);
});

Route::middleware('auth:sanctum')->group( function(){
    Route::prefix('v1')->group( function (){
        Route::get('course/progress-status',[CourseController::class, "progressStatus"]);
        Route::post('user/logout', [UserController::class, "logout"]);
    });
});
