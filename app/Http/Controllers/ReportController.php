<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        $stats = [
            'summary' => [
                'total_reviews' => 0,
                'books_read' => 0,
                'average_rating' => 0,
            ],
            'rating_distribution' => collect([0, 0, 0, 0, 0]),
            'top_rated_books' => collect(),
            'genre_ratings' => collect(),
        ];

        return view('reports.index', compact('stats'));
    }
}
