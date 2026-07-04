<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    public function index()
    {
        $books = Auth::user()
            ->favoriteBooks()
            ->with('genres')
            ->latest('favorites.created_at')
            ->paginate(10);

        return view('favorites.index', compact('books'));
    }

    public function toggle(Book $book)
    {
        Auth::user()->favoriteBooks()->toggle($book);

        return back();
    }
}
