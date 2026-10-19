<?php

use App\Http\Controllers\GudangPeralatanController;
use App\Http\Controllers\CctvController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ApprovalInboxController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\PermohonanBarangMasukController;
use App\Http\Controllers\PermohonanBarangOutController;
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

Route::get('/home', [DashboardController::class, 'index'])->name('home');
Route::group(['middleware' => 'auth'], function () {
    Route::get('dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');
    Route::get('/profile', [UserController::class, 'edit'])->name('profile');
    Route::put('/update-profile', [UserController::class, 'update'])->name('update.profile');
    Route::get('/edit-password', [UserController::class, 'editPassword'])->name('ubah-password');
    Route::patch('/update-password', [UserController::class, 'updatePassword'])->name('update-password');
    Route::get('approval-inbox', [ApprovalInboxController::class, 'index'])->name('approval-inbox.index');
    Route::get('/admin/monitoring-approval', [PermohonanBarangMasukController::class, 'monitoring'])
        ->name('permohonan-barang-masuk.monitoring');
    Route::get('permohonan-barang-masuk/data', [PermohonanBarangMasukController::class, 'data'])->name('permohonan-barang-masuk.data');
    Route::get(
        'permohonan-barang-masuk/{permohonanBarangMasuk}/attachment/{type}',
        [PermohonanBarangMasukController::class, 'attachment']
    )->name('permohonan-barang-masuk.attachment');
    Route::resource('permohonan-barang-masuk', PermohonanBarangMasukController::class)->except(['create', 'edit']);
    Route::patch('permohonan-barang-masuk/{permohonanBarangMasuk}/approve/{stage}', [PermohonanBarangMasukController::class, 'approve'])
        ->name('permohonan-barang-masuk.approve');
    Route::get('permohonan-barang-out/data', [PermohonanBarangOutController::class, 'data'])->name('permohonan-barang-out.data');
    Route::get('permohonan-barang-out/next-code', [PermohonanBarangOutController::class, 'nextCode'])->name('permohonan-barang-out.next-code');
    Route::get('permohonan-barang-out/lookup/{kd_barang}', [PermohonanBarangOutController::class, 'lookup'])
        ->name('permohonan-barang-out.lookup');
    Route::get('permohonan-barang-out/{permohonanBarangOut}/attachment/{type}', [PermohonanBarangOutController::class, 'attachment'])
        ->name('permohonan-barang-out.attachment');
    Route::patch('permohonan-barang-out/{permohonanBarangOut}/decide', [PermohonanBarangOutController::class, 'decide'])
        ->name('permohonan-barang-out.decide');
    Route::resource('permohonan-barang-out', PermohonanBarangOutController::class)->except(['create', 'edit']);
});

Route::group(['middleware' => ['auth', 'checkRole:admin']], function () {
    Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('cctv/data', [CctvController::class, 'data'])->name('cctv.data');
    Route::get('cctv/{cctv}/playback', [CctvController::class, 'playback'])->name('cctv.playback');
    Route::resource('cctv', CctvController::class)->except(['create', 'edit']);
    Route::resource('user', UserController::class);
    Route::resource('pengaturan', PengaturanController::class);
    Route::get('gudang-peralatan/{gudangPeralatan}/cctv', [GudangPeralatanController::class, 'cctv'])->name('gudang-peralatan.cctv');
    Route::resource('gudang-peralatan', GudangPeralatanController::class);
});
