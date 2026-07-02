<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGenreRequest;
use App\Http\Requests\UpdateGenreRequest;
use App\Models\Genre;

class GenreController extends Controller
{
    public function index()
    {
        $genres = Genre::withCount('books')
            ->orderBy('name')
            ->get();

        return view('genres.index', compact('genres'));
    }

    public function create()
    {
        return view('genres.create');
    }

    public function store(StoreGenreRequest $request)
    {
        Genre::create($request->validated());

        return redirect()
            ->route('genres.index')
            ->with('success', 'Genre created successfully.');
    }

    public function show(Genre $genre)
    {
        $books = $genre->books()
            ->with('genres')
            ->latest()
            ->paginate(10);

        return view('genres.show', compact('genre', 'books'));
    }

    public function edit(Genre $genre)
    {
        return view('genres.edit', compact('genre'));
    }

    public function update(UpdateGenreRequest $request, Genre $genre)
    {
        $genre->update($request->validated());

        return redirect()
            ->route('genres.index')
            ->with('success', 'Genre updated successfully.');
    }

    public function destroy(Genre $genre)
    {
        if ($genre->books()->exists()) {
            return redirect()
                ->route('genres.index')
                ->with('error', 'Genres linked to books cannot be deleted.');
        }

        $genre->delete();

        return redirect()
            ->route('genres.index')
            ->with('success', 'Genre deleted successfully.');
    }
}
