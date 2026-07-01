<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_edit_other_users_review(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::create([
            'user_id' => $owner->id,
            'title' => '認可確認用の本',
            'author' => 'テスト著者',
            'isbn' => '9784000000004',
            'published_date' => '2024-01-01',
        ]);

        $review = Review::create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'rating' => 5,
            'body' => '他人のレビュー',
        ]);

        $response = $this->actingAs($otherUser)->get(route('reviews.edit', $review));

        $response->assertForbidden();
    }

    public function test_user_cannot_delete_other_users_review(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::create([
            'user_id' => $owner->id,
            'title' => '削除認可確認用の本',
            'author' => 'テスト著者',
            'isbn' => '9784000000005',
            'published_date' => '2024-01-01',
        ]);

        $review = Review::create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'rating' => 4,
            'body' => '削除されてはいけないレビュー',
        ]);

        $response = $this->actingAs($otherUser)->delete(route('reviews.destroy', $review));

        $response->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'body' => '削除されてはいけないレビュー',
        ]);
    }
}
