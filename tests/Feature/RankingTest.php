<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_ranking_page_can_be_displayed(): void
    {
        $response = $this->get(route('ranking.index'));

        $response->assertOk();
    }

    public function test_ranking_page_displays_reviewed_books(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'Reviewed Book',
            'author' => 'Test Author',
            'isbn' => '9784000000000',
            'published_date' => '2024-01-01',
        ]);

        Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'body' => 'Great book.',
        ]);

        $response = $this->get(route('ranking.index'));

        $response->assertOk();
        $response->assertSee('Reviewed Book');
    }

    public function test_ranking_page_does_not_display_books_without_reviews(): void
    {
        $user = User::factory()->create();

        Book::create([
            'user_id' => $user->id,
            'title' => 'No Review Book',
            'author' => 'Test Author',
            'isbn' => '9784000000000',
            'published_date' => '2024-01-01',
        ]);

        $response = $this->get(route('ranking.index'));

        $response->assertOk();
        $response->assertDontSee('No Review Book');
    }

    public function test_ranking_orders_books_by_average_rating(): void
    {
        $user = User::factory()->create();

        $highRatedBook = Book::create([
            'user_id' => $user->id,
            'title' => 'High Rated Book',
            'author' => 'Test Author',
            'isbn' => '9784000000001',
            'published_date' => '2024-01-01',
        ]);

        $lowRatedBook = Book::create([
            'user_id' => $user->id,
            'title' => 'Low Rated Book',
            'author' => 'Test Author',
            'isbn' => '9784000000002',
            'published_date' => '2024-01-01',
        ]);

        Review::create([
            'user_id' => $user->id,
            'book_id' => $highRatedBook->id,
            'rating' => 5,
            'body' => 'Great book.',
        ]);

        Review::create([
            'user_id' => User::factory()->create()->id,
            'book_id' => $lowRatedBook->id,
            'rating' => 2,
            'body' => 'Not bad.',
        ]);

        $response = $this->get(route('ranking.index'));

        $response->assertOk();
        $response->assertSeeInOrder([
            'High Rated Book',
            'Low Rated Book',
        ]);
    }
}
