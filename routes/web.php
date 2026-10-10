<?php

use App\Http\Controllers\GudangPeralatanController;
use App\Http\Controllers\HomeController;


use App\Http\Controllers\PengaturanController;

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
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

Route::get('/', function () {
    return view('welcome');
})->middleware('auth');

Auth::routes();


Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::group(['middleware' => 'auth'], function () {
    Route::get('/profile', [UserController::class, 'edit'])->name('profile');
    Route::put('/update-profile', [UserController::class, 'update'])->name('update.profile');
    Route::get('/edit-password', [UserController::class, 'editPassword'])->name('ubah-password');
    Route::patch('/update-password', [UserController::class, 'updatePassword'])->name('update-password');
});


Route::group(['middleware' => ['auth', 'checkRole:admin']], function () {
    Route::get('/admin/dashboard', [HomeController::class, 'admin'])->name('admin.dashboard');
    
    Route::resource('user', UserController::class);
    Route::resource('jadwal', JadwalController::class);
    Route::resource('pengumuman-sekolah', PengumumanSekolahController::class);
    Route::resource('pengaturan', PengaturanController::class);
    Route::get('gudang-peralatan/{gudangPeralatan}/cctv', [GudangPeralatanController::class, 'cctv'])->name('gudang-peralatan.cctv');
    Route::resource('gudang-peralatan', GudangPeralatanController::class);
});
