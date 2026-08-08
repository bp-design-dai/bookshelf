<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
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
            ->assertJsonPath('data.genres.0.name', 'Novel');
    }

    public function test_get_missing_book_returns_404(): void
    {
        $response = $this->getJson('/api/v1/books/999999');

        $response->assertNotFound();
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
            'genres' => [$genre->id],
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
            ->assertJsonValidationErrors([
                'title',
                'author',
                'isbn',
                'published_date',
                'genres',
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
            'genres' => [$genre->id],
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
            'genres' => [$genre->id],
        ]);

        $response->assertNotFound();
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
            ->assertJsonValidationErrors([
                'title',
                'author',
                'isbn',
                'published_date',
                'genres',
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

        $response->assertNotFound();
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
            'genres' => [$genre->id],
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
            'genres' => [$genre->id],
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
            'genres' => [$genre->id],
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
            'genres' => [$genre->id],
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
}
