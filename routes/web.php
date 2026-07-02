<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BookController::class, 'index'])->name('books.index');

Route::middleware('auth')->group(function () {
    Route::resource('books', BookController::class)->except(['index']);

    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::get('/reviews/{review}/edit', [ReviewController::class, 'edit'])->name('reviews.edit');
    Route::put('/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

    Route::resource('genres', GenreController::class)->except(['index', 'show']);

    Route::post('/books/{book}/favorite', function () {
        return back();
    })->name('favorites.toggle');

    Route::get('/favorites', function () {
        return view('favorites.index', ['books' => collect()]);
    })->name('favorites.index');

    Route::post('/reviews/{review}/like', function () {
        return back();
    })->name('reviews.like');
});

Route::resource('genres', GenreController::class)->only(['index', 'show']);

Route::get('/ranking', function () {
    return view('ranking.index', ['books' => collect()]);
})->name('ranking.index');

Route::get('/home', function () {
    return redirect()->route('books.index');
})->middleware('auth');
