<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewLikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_like_review(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'Test Book',
            'author' => 'Test Author',
            'isbn' => '9784000000000',
            'published_date' => '2024-01-01',
        ]);

        $review = Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'body' => 'Great book.',
        ]);

        $response = $this->post(route('reviews.like', $review));

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_like_review(): void
    {
        $reviewUser = User::factory()->create();
        $likeUser = User::factory()->create();

        $book = Book::create([
            'user_id' => $reviewUser->id,
            'title' => 'Test Book',
            'author' => 'Test Author',
            'isbn' => '9784000000000',
            'published_date' => '2024-01-01',
        ]);

        $review = Review::create([
            'user_id' => $reviewUser->id,
            'book_id' => $book->id,
            'rating' => 5,
            'body' => 'Great book.',
        ]);

        $response = $this->actingAs($likeUser)->post(route('reviews.like', $review));

        $response->assertRedirect();
        $this->assertDatabaseHas('review_likes', [
            'user_id' => $likeUser->id,
            'review_id' => $review->id,
        ]);
    }

    public function test_user_can_unlike_review(): void
    {
        $reviewUser = User::factory()->create();
        $likeUser = User::factory()->create();

        $book = Book::create([
            'user_id' => $reviewUser->id,
            'title' => 'Test Book',
            'author' => 'Test Author',
            'isbn' => '9784000000000',
            'published_date' => '2024-01-01',
        ]);

        $review = Review::create([
            'user_id' => $reviewUser->id,
            'book_id' => $book->id,
            'rating' => 5,
            'body' => 'Great book.',
        ]);

        $likeUser->likedReviews()->attach($review);

        $response = $this->actingAs($likeUser)->post(route('reviews.like', $review));

        $response->assertRedirect();
        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $likeUser->id,
            'review_id' => $review->id,
        ]);
    }
}
