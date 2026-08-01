<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexBookRequest;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Support\Facades\Auth;

class BookController extends Controller
{
    public function index(IndexBookRequest $request)
    {
        $keyword = $request->validated('keyword');
        $genreId = $request->validated('genre_id');
        $sort = $request->validated('sort', 'latest');

        $books = Book::query()
            ->with('genres')
            ->withAvg('reviews', 'rating')
            ->when($keyword, function ($query, $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query
                        ->where('title', 'like', "%{$keyword}%")
                        ->orWhere('author', 'like', "%{$keyword}%");
                });
            })
            ->when($genreId, function ($query, $genreId) {
                $query->whereHas('genres', function ($query) use ($genreId) {
                    $query->where('genres.id', $genreId);
                });
            })
            ->when($sort === 'latest', fn ($query) => $query->latest())
            ->when($sort === 'oldest', fn ($query) => $query->oldest())
            ->when(
                $sort === 'rating',
                fn ($query) => $query
                    ->orderByDesc('reviews_avg_rating')
                    ->latest('books.created_at')
            )
            ->when(
                $sort === 'title',
                fn ($query) => $query
                    ->orderBy('title')
                    ->latest('books.created_at')
            )
            ->paginate(10)
            ->withQueryString();

        $genres = Genre::orderBy('name')->get();

        return view('books.index', compact('books', 'genres'));
    }

    public function create()
    {
        $genres = Genre::orderBy('name')->get();

        return view('books.create', compact('genres'));
    }

    public function store(StoreBookRequest $request)
    {
        $book = Book::create([
            ...$request->safe()->except('genres'),
            'user_id' => Auth::id(),
        ]);

        $book->genres()->attach($request->validated('genres'));

        return redirect()->route('books.show', $book);
    }

    public function show(Book $book)
    {
        $book->load(['user', 'genres', 'reviews.user']);

        return view('books.show', compact('book'));
    }

    public function edit(Book $book)
    {
        $this->authorize('update', $book);

        $genres = Genre::orderBy('name')->get();

        return view('books.edit', compact('book', 'genres'));
    }

    public function update(UpdateBookRequest $request, Book $book)
    {
        $this->authorize('update', $book);

        $book->update($request->safe()->except('genres'));
        $book->genres()->sync($request->validated('genres'));

        return redirect()->route('books.show', $book);
    }

    public function destroy(Book $book)
    {
        $this->authorize('delete', $book);

        $book->delete();

        return redirect()->route('books.index');
    }
}
