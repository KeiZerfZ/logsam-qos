<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route untuk menampilkan halaman utama (dashboard)
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Route untuk menyimpan data baru dari form manual
Route::post('/loggings', [DashboardController::class, 'store'])->name('loggings.store');

// Route untuk menghapus data log
Route::delete('/loggings/{log}', [DashboardController::class, 'destroy'])->name('loggings.destroy');

Route::get('/loggings/{log}/detail', [DashboardController::class, 'showDetail'])->name('loggings.detail');
