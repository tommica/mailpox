<?php

use Illuminate\Support\Facades\Route;
use Tommica\Mailpox\Http\Controllers\MessageController;

Route::get('/', [MessageController::class, 'index'])->name('mailpox.index');
Route::delete('/', [MessageController::class, 'destroyAll'])->name('mailpox.destroy-all');
Route::get('/poll', [MessageController::class, 'poll'])->name('mailpox.poll');
Route::get('/{message}', [MessageController::class, 'show'])->name('mailpox.show');
Route::get('/{message}/html', [MessageController::class, 'html'])->name('mailpox.html');
Route::get('/{message}/attachments/{attachment}', [MessageController::class, 'attachment'])->name('mailpox.attachments.show');
Route::delete('/{message}', [MessageController::class, 'destroy'])->name('mailpox.destroy');
