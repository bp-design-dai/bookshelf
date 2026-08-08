<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\ReviewLike;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_book_list(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => 'Novel']);

        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'API Book',
            'author' => 'API Author',
            'isbn' => '9784000000000',
            'published_date' => '2024-01-01',
        ]);

        $book->genres()->attach($genre);

        $response = $this->getJson('/api/v1/books');

        $response->assertOk()
            ->assertJsonPath('data.0.title', 'API Book')
            ->assertJsonPath('data.0.genres.0.name', 'Novel');
    }

    public function test_book_list_can_be_searched_by_title_keyword(): void
    {
        $user = User::factory()->create();

        Book::create([
            'user_id' => $user->id,
            'title' => 'Laravel入門',
            'author' => '山田太郎',
            'isbn' => '9784000000001',
            'published_date' => '2024-01-01',
        ]);

        Book::create([
            'user_id' => $user->id,
            'title' => 'PHP実践',
            'author' => '佐藤花子',
            'isbn' => '9784000000002',
            'published_date' => '2024-01-02',
        ]);

        $response = $this->getJson('/api/v1/books?keyword=Laravel');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Laravel入門');
    }

    public function test_book_list_can_be_searched_by_author_keyword(): void
    {
        $user = User::factory()->create();

        Book::create([
            'user_id' => $user->id,
            'title' => 'Book A',
            'author' => '山田太郎',
            'isbn' => '9784000000003',
            'published_date' => '2024-01-01',
        ]);

        Book::create([
            'user_id' => $user->id,
            'title' => 'Book B',
            'author' => '佐藤花子',
            'isbn' => '9784000000004',
            'published_date' => '2024-01-02',
        ]);

        $response = $this->getJson('/api/v1/books?keyword=山田');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.author', '山田太郎');
    }

    public function test_book_list_can_be_filtered_by_genre(): void
    {
        $user = User::factory()->create();
        $novel = Genre::create(['name' => 'Novel']);
        $business = Genre::create(['name' => 'Business']);

        $novelBook = Book::create([
            'user_id' => $user->id,
            'title' => 'Novel Book',
            'author' => 'Author A',
            'isbn' => '9784000000005',
            'published_date' => '2024-01-01',
        ]);

        $businessBook = Book::create([
            'user_id' => $user->id,
            'title' => 'Business Book',
            'author' => 'Author B',
            'isbn' => '9784000000006',
            'published_date' => '2024-01-02',
        ]);

        $novelBook->genres()->attach($novel);
        $businessBook->genres()->attach($business);

        $response = $this->getJson("/api/v1/books?genre_id={$novel->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Novel Book');
    }

    public function test_book_list_combines_keyword_and_genre_filters_with_and_condition(): void
    {
        $user = User::factory()->create();
        $novel = Genre::create(['name' => 'Novel']);
        $business = Genre::create(['name' => 'Business']);

        $targetBook = Book::create([
            'user_id' => $user->id,
            'title' => 'Laravel Novel',
            'author' => 'Author A',
            'isbn' => '9784000000007',
            'published_date' => '2024-01-01',
        ]);

        $otherGenreBook = Book::create([
            'user_id' => $user->id,
            'title' => 'Laravel Business',
            'author' => 'Author B',
            'isbn' => '9784000000008',
            'published_date' => '2024-01-02',
        ]);

        $otherKeywordBook = Book::create([
            'user_id' => $user->id,
            'title' => 'PHP Novel',
            'author' => 'Author C',
            'isbn' => '9784000000009',
            'published_date' => '2024-01-03',
        ]);

        $targetBook->genres()->attach($novel);
        $otherGenreBook->genres()->attach($business);
        $otherKeywordBook->genres()->attach($novel);

        $response = $this->getJson(
            "/api/v1/books?keyword=Laravel&genre_id={$novel->id}"
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Laravel Novel');
    }

    public function test_book_list_returns_empty_data_when_no_books_match(): void
    {
        $user = User::factory()->create();

        Book::create([
            'user_id' => $user->id,
            'title' => 'Laravel Book',
            'author' => 'API Author',
            'isbn' => '9784000000010',
            'published_date' => '2024-01-01',
        ]);

        $response = $this->getJson('/api/v1/books?keyword=NotFoundKeyword');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_book_list_uses_ten_items_per_page_by_default(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 11; $i++) {
            Book::create([
                'user_id' => $user->id,
                'title' => "Book {$i}",
                'author' => 'API Author',
                'isbn' => sprintf('978400001%04d', $i),
                'published_date' => '2024-01-01',
            ]);
        }

        $response = $this->getJson('/api/v1/books');

        $response->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 11);
    }

    public function test_book_list_can_change_per_page(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 5; $i++) {
            Book::create([
                'user_id' => $user->id,
                'title' => "Book {$i}",
                'author' => 'API Author',
                'isbn' => sprintf('978400002%04d', $i),
                'published_date' => '2024-01-01',
            ]);
        }

        $response = $this->getJson('/api/v1/books?per_page=3');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.per_page', 3)
            ->assertJsonPath('meta.total', 5);
    }

    public function test_book_list_is_ordered_by_newest_first(): void
    {
        $user = User::factory()->create();

        $oldBook = Book::create([
            'user_id' => $user->id,
            'title' => 'Old Book',
            'author' => 'API Author',
            'isbn' => '9784000000011',
            'published_date' => '2024-01-01',
        ]);

        $newBook = Book::create([
            'user_id' => $user->id,
            'title' => 'New Book',
            'author' => 'API Author',
            'isbn' => '9784000000012',
            'published_date' => '2024-01-01',
        ]);

        $oldBook->forceFill([
            'created_at' => now()->subDay(),
        ])->save();

        $newBook->forceFill([
            'created_at' => now(),
        ])->save();

        $response = $this->getJson('/api/v1/books');

        $response->assertOk()
            ->assertJsonPath('data.0.title', 'New Book')
            ->assertJsonPath('data.1.title', 'Old Book');
    }

    public function test_book_list_invalid_page_returns_422(): void
    {
        $response = $this->getJson('/api/v1/books?page=0');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('page');
    }

    public function test_book_list_invalid_per_page_returns_422(): void
    {
        $response = $this->getJson('/api/v1/books?per_page=101');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
    }

    public function test_book_list_invalid_genre_id_returns_422(): void
    {
        $response = $this->getJson('/api/v1/books?genre_id=999999');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('genre_id');
    }

    public function test_can_get_book_detail(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => 'Novel']);

        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'API Book',
            'author' => 'API Author',
            'isbn' => '9784000000000',
            'published_date' => '2024-01-01',
        ]);

        $book->genres()->attach($genre);

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $book->id)
            ->assertJsonPath('data.title', 'API Book')
            ->assertJsonPath('data.published_date', '2024-01-01')
            ->assertJsonPath('data.genres.0.name', 'Novel');
    }

    public function test_book_detail_includes_reviews_with_user_likes_and_newest_order(): void
    {
        $bookOwner = User::factory()->create();
        $oldReviewer = User::factory()->create(['name' => 'Old Reviewer']);
        $newReviewer = User::factory()->create(['name' => 'New Reviewer']);
        $likeUser = User::factory()->create();

        $book = Book::create([
            'user_id' => $bookOwner->id,
            'title' => 'Reviewed Book',
            'author' => 'API Author',
            'isbn' => '9784000000016',
            'published_date' => '2024-01-01',
        ]);

        $oldReview = Review::create([
            'user_id' => $oldReviewer->id,
            'book_id' => $book->id,
            'rating' => 3,
            'body' => 'Old Review',
        ]);

        $newReview = Review::create([
            'user_id' => $newReviewer->id,
            'book_id' => $book->id,
            'rating' => 5,
            'body' => 'New Review',
        ]);

        $oldReview->forceFill([
            'created_at' => now()->subDay(),
        ])->save();

        $newReview->forceFill([
            'created_at' => now(),
        ])->save();

        ReviewLike::create([
            'user_id' => $likeUser->id,
            'review_id' => $newReview->id,
        ]);

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertOk()
            ->assertJsonCount(2, 'data.reviews')
            ->assertJsonPath('data.reviews.0.id', $newReview->id)
            ->assertJsonPath('data.reviews.0.user_name', 'New Reviewer')
            ->assertJsonPath('data.reviews.0.rating', 5)
            ->assertJsonPath('data.reviews.0.comment', 'New Review')
            ->assertJsonPath('data.reviews.0.likes_count', 1)
            ->assertJsonPath('data.reviews.1.id', $oldReview->id)
            ->assertJsonPath('data.reviews.1.user_name', 'Old Reviewer')
            ->assertJsonPath('data.reviews.1.likes_count', 0);
    }

    public function test_get_missing_book_returns_404(): void
    {
        $response = $this->getJson('/api/v1/books/999999');

        $response->assertNotFound()
            ->assertJsonPath('message', '書籍が見つかりません');
    }

    public function test_can_create_book(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => 'Novel']);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', [
            'title' => 'Created API Book',
            'author' => 'API Author',
            'isbn' => '9784000000000',
            'published_date' => '2024-01-01',
            'description' => 'Created from API.',
            'image_url' => null,
            'genre_ids' => [$genre->id],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Created API Book')
            ->assertJsonPath('data.genres.0.name', 'Novel');

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'title' => 'Created API Book',
            'isbn' => '9784000000000',
        ]);
    }

    public function test_create_book_validation_error_returns_422(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', []);

        $response->assertUnprocessable()
            ->assertJsonStructure([
                'message',
                'errors',
            ])
            ->assertJsonValidationErrors([
                'title',
                'author',
                'isbn',
                'published_date',
                'genre_ids',
            ]);
    }

    public function test_can_update_book(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => 'Novel']);

        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'Old API Book',
            'author' => 'Old Author',
            'isbn' => '9784000000000',
            'published_date' => '2024-01-01',
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => 'Updated API Book',
            'author' => 'Updated Author',
            'isbn' => '9784000000000',
            'published_date' => '2024-02-01',
            'description' => 'Updated from API.',
            'image_url' => null,
            'genre_ids' => [$genre->id],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated API Book')
            ->assertJsonPath('data.genres.0.name', 'Novel');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'Updated API Book',
        ]);
    }

    public function test_update_missing_book_returns_404(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => 'Novel']);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/books/999999', [
            'title' => 'Missing Book',
            'author' => 'Missing Author',
            'isbn' => '9784000000000',
            'published_date' => '2024-01-01',
            'description' => null,
            'image_url' => null,
            'genre_ids' => [$genre->id],
        ]);

        $response->assertNotFound()
            ->assertJsonPath('message', '書籍が見つかりません');
    }

    public function test_update_book_validation_error_returns_422(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'API Book',
            'author' => 'API Author',
            'isbn' => '9784000000000',
            'published_date' => '2024-01-01',
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/books/{$book->id}", []);

        $response->assertUnprocessable()
            ->assertJsonStructure([
                'message',
                'errors',
            ])
            ->assertJsonValidationErrors([
                'title',
                'author',
                'isbn',
                'published_date',
                'genre_ids',
            ]);
    }

    public function test_can_delete_book(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'Delete API Book',
            'author' => 'API Author',
            'isbn' => '9784000000000',
            'published_date' => '2024-01-01',
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    public function test_delete_missing_book_returns_404(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/books/999999');

        $response->assertNotFound()
            ->assertJsonPath('message', '書籍が見つかりません');
    }

    public function test_unauthenticated_user_cannot_create_book(): void
    {
        $genre = Genre::create(['name' => 'Novel']);

        $response = $this->postJson('/api/v1/books', [
            'title' => 'Unauthorized Book',
            'author' => 'API Author',
            'isbn' => '9784000000001',
            'published_date' => '2024-01-01',
            'description' => null,
            'image_url' => null,
            'genre_ids' => [$genre->id],
        ]);

        $response->assertUnauthorized();
    }

    public function test_unauthenticated_user_cannot_update_book(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => 'Novel']);

        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'API Book',
            'author' => 'API Author',
            'isbn' => '9784000000000',
            'published_date' => '2024-01-01',
        ]);

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => 'Unauthorized Update',
            'author' => 'API Author',
            'isbn' => '9784000000000',
            'published_date' => '2024-01-01',
            'description' => null,
            'image_url' => null,
            'genre_ids' => [$genre->id],
        ]);

        $response->assertUnauthorized();
    }

    public function test_unauthenticated_user_cannot_delete_book(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'Delete API Book',
            'author' => 'API Author',
            'isbn' => '9784000000000',
            'published_date' => '2024-01-01',
        ]);

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertUnauthorized();
    }

    public function test_authenticated_non_owner_cannot_update_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $genre = Genre::create(['name' => 'Novel']);

        $book = Book::create([
            'user_id' => $owner->id,
            'title' => 'Owner Book',
            'author' => 'API Author',
            'isbn' => '9784000000000',
            'published_date' => '2024-01-01',
        ]);

        Sanctum::actingAs($otherUser);

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => 'Unauthorized Update',
            'author' => 'API Author',
            'isbn' => '9784000000000',
            'published_date' => '2024-01-01',
            'description' => null,
            'image_url' => null,
            'genre_ids' => [$genre->id],
        ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'Owner Book',
        ]);
    }

    public function test_authenticated_non_owner_cannot_delete_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::create([
            'user_id' => $owner->id,
            'title' => 'Owner Book',
            'author' => 'API Author',
            'isbn' => '9784000000000',
            'published_date' => '2024-01-01',
        ]);

        Sanctum::actingAs($otherUser);

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    public function test_create_book_uses_authenticated_user_id(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $genre = Genre::create(['name' => 'Novel']);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', [
            'user_id' => $otherUser->id,
            'title' => 'Authenticated User Book',
            'author' => 'API Author',
            'isbn' => '9784000000002',
            'published_date' => '2024-01-01',
            'description' => null,
            'image_url' => null,
            'genre_ids' => [$genre->id],
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'title' => 'Authenticated User Book',
        ]);

        $this->assertDatabaseMissing('books', [
            'user_id' => $otherUser->id,
            'title' => 'Authenticated User Book',
        ]);
    }

    public function test_create_book_removes_duplicate_genre_ids(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => 'Novel']);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', [
            'title' => 'Duplicate Genre Book',
            'author' => 'API Author',
            'isbn' => '9784000000013',
            'published_date' => '2024-01-01',
            'description' => null,
            'image_url' => null,
            'genre_ids' => [$genre->id, $genre->id],
        ]);

        $response->assertCreated();

        $book = Book::where('isbn', '9784000000013')->firstOrFail();

        $this->assertSame(1, $book->genres()->count());
    }

    public function test_create_book_rejects_more_than_three_unique_genre_ids(): void
    {
        $user = User::factory()->create();

        $genres = collect(range(1, 4))->map(
            fn ($i) => Genre::create(['name' => "Genre {$i}"])
        );

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', [
            'title' => 'Too Many Genres Book',
            'author' => 'API Author',
            'isbn' => '9784000000014',
            'published_date' => '2024-01-01',
            'description' => null,
            'image_url' => null,
            'genre_ids' => $genres->pluck('id')->all(),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('genre_ids');
    }

    public function test_update_book_removes_duplicate_genre_ids(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => 'Novel']);

        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'API Book',
            'author' => 'API Author',
            'isbn' => '9784000000015',
            'published_date' => '2024-01-01',
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => 'Updated API Book',
            'author' => 'API Author',
            'isbn' => '9784000000015',
            'published_date' => '2024-01-01',
            'description' => null,
            'image_url' => null,
            'genre_ids' => [$genre->id, $genre->id],
        ]);

        $response->assertOk();

        $this->assertSame(1, $book->fresh()->genres()->count());
    }
}
