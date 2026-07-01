<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_rating_must_be_between_1_and_5(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'レビュー確認用',
            'author' => 'テスト著者',
            'isbn' => '9784000000002',
            'published_date' => '2024-01-01',
        ]);

        $response = $this->actingAs($user)->post(route('reviews.store', $book), [
            'rating' => 6,
            'comment' => '評価範囲外テスト',
        ]);

        $response->assertSessionHasErrors('rating');

        $this->assertDatabaseMissing('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 6,
        ]);
    }

    public function test_review_comment_must_be_1000_characters_or_less(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'レビュー本文確認用',
            'author' => 'テスト著者',
            'isbn' => '9784000000003',
            'published_date' => '2024-01-01',
        ]);

        $response = $this->actingAs($user)->post(route('reviews.store', $book), [
            'rating' => 5,
            'comment' => str_repeat('あ', 1001),
        ]);

        $response->assertSessionHasErrors('comment');

        $this->assertDatabaseMissing('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }
}
