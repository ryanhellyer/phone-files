<?php

use App\Http\Controllers\FileBrowserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FileBrowserController::class, 'index'])->name('files.index');
Route::get('/list', [FileBrowserController::class, 'list'])->name('files.list');
Route::delete('/delete', [FileBrowserController::class, 'destroy'])->name('files.destroy');
Route::post('/keep', [FileBrowserController::class, 'keepOnly'])->name('files.keep');
Route::get('/trash', [FileBrowserController::class, 'trash'])->name('files.trash');
Route::post('/restore', [FileBrowserController::class, 'restore'])->name('files.restore');

/**
 * Originals and Glide-generated thumbnails. Files live outside public/, so
 * Laravel serves both. Dimensioned names (photo-96x96.jpg) are handled by
 * ImageController/Glide; other paths stream the original file.
 */
Route::get('files/{path}', [FileBrowserController::class, 'show'])
    ->where('path', '.*')
    ->name('files.show');
