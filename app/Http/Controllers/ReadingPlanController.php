<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\ReadingPlan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ReadingPlanController extends Controller
{
    public function index(Request $request): View
    {
        $currentStatus = $request->query('status', '');

        $readingPlans = $request->user()
            ->readingPlans()
            ->with('book')
            ->when(
                $currentStatus,
                fn ($query) => $query->where('status', $currentStatus)
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('reading-plans.index', compact(
            'readingPlans',
            'currentStatus'
        ));
    }

    public function create(): View
    {
        $books = Book::query()
            ->orderBy('title')
            ->get();

        return view('reading-plans.create', compact('books'));
    }

    public function edit(ReadingPlan $readingPlan): View
    {
        $readingPlan->load('book');

        return view('reading-plans.edit', compact('readingPlan'));
    }
}
