<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ChatController;
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
Route::get('/', [RegisterController::class, 'index']);
Route::post('/register', [RegisterController::class, 'register']);
Route::get('/login', [LoginController::class, 'index'])->name('login');
Route::post('/login/auth', [LoginController::class, 'login']);

Route::middleware(['auth:user'])->group(function(){
    Route::get('/home', [UserController::class, 'home']);
    Route::get('/logout', [UserController::class, 'logout']);
    Route::get('/profile', [UserController::class, 'profile']);
    Route::put('/profile/basicUpdate', [UserController::class, 'basicUpdate']);
    Route::put('/profile/changeImage', [UserController::class, 'changeImage']);
    Route::put('/profile/passwordUpdate', [UserController::class, 'passwordUpdate']);

    Route::get('/home/message/{id}/{user}', [ChatController::class, 'chatRoom'])->where(['id' => '[0-9]+', 'user' => '[a-zA-Z_]+']);
    Route::post('/home/message/send', [ChatController::class, 'saveMessage'])->where(['id' => '[0-9]+', 'user' => '[a-zA-Z_]+']);
    Route::get('/home/message/pull/{id}/{user}', [ChatController::class, 'infiniteChatRoom'])->where(['id' => '[0-9]+', 'user' => '[a-zA-Z_]+']);
    Route::get('/home/message/chats/{id}/{user}', [ChatController::class, 'infineteChats'])->where(['id' => '[0-9]+', 'user' => '[a-zA-Z_]+']);
});


