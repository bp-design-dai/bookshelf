<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\Review;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ReportController extends Controller
{
    /**
     * ログインユーザーの読書レポートを表示する。
     */
    public function index(Request $request): View
    {
        $reviews = $request->user()
            ->reviews()
            ->with('book.genres')
            ->get();

        $stats = [
            'summary' => [
                'total_reviews' => $reviews->count(),
                'books_read' => $reviews
                    ->pluck('book_id')
                    ->unique()
                    ->count(),
                'average_rating' => (float) ($reviews->avg('rating') ?? 0),
            ],
            'rating_distribution' => collect(range(1, 5))
                ->map(
                    fn (int $rating): int => $reviews
                        ->where('rating', $rating)
                        ->count()
                ),
            'top_rated_books' => $reviews
                ->filter(
                    fn (Review $review): bool => $review->rating >= 4
                )
                ->sort(
                    function (Review $first, Review $second): int {
                        $ratingComparison = $second->rating <=> $first->rating;

                        if ($ratingComparison !== 0) {
                            return $ratingComparison;
                        }

                        return $second->updated_at->getTimestamp()
                            <=> $first->updated_at->getTimestamp();
                    }
                )
                ->take(5)
                ->values()
                ->map(
                    fn (Review $review): array => [
                        'id' => $review->book->id,
                        'title' => $review->book->title,
                        'author' => $review->book->author,
                        'rating' => $review->rating,
                    ]
                ),
            'genre_ratings' => $reviews
                ->flatMap(
                    fn (Review $review): Collection => $review->book->genres
                        ->map(
                            fn (Genre $genre): array => [
                                'genre' => $genre,
                                'rating' => $review->rating,
                            ]
                        )
                )
                ->groupBy(
                    fn (array $item): int => $item['genre']->id
                )
                ->map(
                    function (Collection $items): array {
                        /** @var Genre $genre */
                        $genre = $items->first()['genre'];

                        return [
                            'id' => $genre->id,
                            'name' => $genre->name,
                            'count' => $items->count(),
                            'average_rating' => (float) $items->avg('rating'),
                        ];
                    }
                )
                ->sort(
                    function (array $first, array $second): int {
                        $averageComparison = $second['average_rating']
                            <=> $first['average_rating'];

                        if ($averageComparison !== 0) {
                            return $averageComparison;
                        }

                        return $second['count'] <=> $first['count'];
                    }
                )
                ->take(5)
                ->values(),
        ];

        return view('reports.index', compact('stats'));
    }
}
