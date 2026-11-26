<?php

use App\Http\Controllers\TriageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TriageController::class, 'index'])->name('portal.index');
Route::post('/triase', [TriageController::class, 'submit'])->name('portal.submit');

Route::get('/antrean', [TriageController::class, 'queue'])->name('queue.index');
Route::get('/antrean/{complaint}', [TriageController::class, 'show'])->name('queue.show');
Route::patch('/antrean/{complaint}/status', [TriageController::class, 'updateStatus'])->name('queue.status');

Route::get('/riset', [TriageController::class, 'research'])->name('research.index');
Route::post('/api/normalisasi', [TriageController::class, 'normalizePreview'])->name('api.normalize');
