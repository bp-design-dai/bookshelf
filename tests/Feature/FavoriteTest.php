<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_favorites_page(): void
    {
        $response = $this->get(route('favorites.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_add_book_to_favorites(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'Test Book',
            'author' => 'Test Author',
            'isbn' => '9784000000000',
            'published_date' => '2024-01-01',
        ]);

        $response = $this->actingAs($user)->post(route('favorites.toggle', $book));

        $response->assertRedirect();
        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_user_can_remove_book_from_favorites(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'Test Book',
            'author' => 'Test Author',
            'isbn' => '9784000000000',
            'published_date' => '2024-01-01',
        ]);

        $user->favoriteBooks()->attach($book);

        $response = $this->actingAs($user)->post(route('favorites.toggle', $book));

        $response->assertRedirect();
        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_favorites_page_displays_favorite_books(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'Favorite Book',
            'author' => 'Test Author',
            'isbn' => '9784000000000',
            'published_date' => '2024-01-01',
        ]);

        $user->favoriteBooks()->attach($book);

        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertOk();
        $response->assertSee('Favorite Book');
    }
}
