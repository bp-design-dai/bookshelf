<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;

Route::get('/', [BookController::class, 'index'])->name('books.index');

Route::middleware('auth')->group(function () {
    Route::resource('books', BookController::class)->except(['index']);

    Route::post('/books/{book}/favorite', function () {
        return back();
    })->name('favorites.toggle');

    Route::get('/favorites', function () {
        return view('favorites.index', ['books' => collect()]);
    })->name('favorites.index');

    Route::post('/books/{book}/reviews', function () {
        return back();
    })->name('reviews.store');

    Route::get('/reviews/{review}/edit', function () {
        abort(404);
    })->name('reviews.edit');

    Route::put('/reviews/{review}', function () {
        return back();
    })->name('reviews.update');

    Route::delete('/reviews/{review}', function () {
        return back();
    })->name('reviews.destroy');

    Route::post('/reviews/{review}/like', function () {
        return back();
    })->name('reviews.like');

    Route::resource('genres', Controller::class)->only([]);
});

Route::get('/genres', function () {
    return view('genres.index', ['genres' => collect()]);
})->name('genres.index');

Route::get('/ranking', function () {
    return view('ranking.index', ['books' => collect()]);
})->name('ranking.index');

Route::get('/home', function () {
    return redirect()->route('books.index');
})->middleware('auth');
