<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexBookRequest;
use App\Http\Requests\Api\V1\StoreBookRequest;
use App\Http\Requests\Api\V1\UpdateBookRequest;
use App\Http\Resources\BookDetailResource;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookController extends Controller
{
    public function index(IndexBookRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();
        $perPage = $validated['per_page'] ?? 10;

        $books = Book::with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->when(
                $validated['keyword'] ?? null,
                function ($query, $keyword) {
                    $query->where(function ($query) use ($keyword) {
                        $query->where('title', 'like', "%{$keyword}%")
                            ->orWhere('author', 'like', "%{$keyword}%");
                    });
                }
            )
            ->when(
                $validated['genre_id'] ?? null,
                function ($query, $genreId) {
                    $query->whereHas('genres', function ($query) use ($genreId) {
                        $query->where('genres.id', $genreId);
                    });
                }
            )
            ->latest()
            ->paginate($perPage);

        return BookResource::collection($books);
    }

    public function store(StoreBookRequest $request): BookResource
    {
        $book = $request->user()->books()->create(
            $request->safe()->except('genre_ids')
        );

        $book->genres()->attach($request->validated('genre_ids'));

        $book->load('genres');

        return new BookResource($book);
    }

    public function show(Book $book): BookDetailResource
    {
        $book->load([
            'genres',
            'reviews' => function ($query) {
                $query->with('user')
                    ->withCount('reviewLikes')
                    ->latest();
            },
        ])
            ->loadAvg('reviews', 'rating')
            ->loadCount('reviews');

        return new BookDetailResource($book);
    }

    public function update(UpdateBookRequest $request, Book $book): BookResource
    {
        $this->authorize('update', $book);

        $book->update($request->safe()->except('genre_ids'));

        $book->genres()->sync($request->validated('genre_ids'));

        $book->load('genres')
            ->loadAvg('reviews', 'rating')
            ->loadCount('reviews');

        return new BookResource($book);
    }

    public function destroy(Book $book): JsonResponse
    {
        $this->authorize('delete', $book);

        $book->delete();

        return response()->json(null, 204);
    }
}
