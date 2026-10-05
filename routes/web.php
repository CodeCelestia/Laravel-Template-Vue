<?php

use App\Http\Controllers\MunicipalityController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);
    Route::resource('permissions', PermissionController::class)->only(['store', 'update', 'destroy']);

    Route::resource('municipalities', MunicipalityController::class);

    Route::post('notes/reorder', [NoteController::class, 'reorder'])->name('notes.reorder');
    Route::resource('notes', NoteController::class);
});

require __DIR__.'/settings.php';
