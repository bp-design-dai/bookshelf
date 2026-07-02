<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_genre(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('genres.store'), [
            'name' => 'Mystery',
        ]);

        $response->assertRedirect(route('genres.index'));
        $this->assertDatabaseHas('genres', [
            'name' => 'Mystery',
        ]);
    }

    public function test_genre_name_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('genres.store'), [
            'name' => '',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_genre_name_must_be_unique(): void
    {
        $user = User::factory()->create();

        Genre::create(['name' => 'Novel']);

        $response = $this->actingAs($user)->post(route('genres.store'), [
            'name' => 'Novel',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_user_can_update_genre(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => 'Old Name']);

        $response = $this->actingAs($user)->put(route('genres.update', $genre), [
            'name' => 'New Name',
        ]);

        $response->assertRedirect(route('genres.index'));
        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => 'New Name',
        ]);
    }

    public function test_genre_linked_to_book_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => 'Novel']);

        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'Test Book',
            'author' => 'Test Author',
            'isbn' => '9784000000000',
            'published_date' => '2024-01-01',
        ]);

        $book->genres()->attach($genre);

        $response = $this->actingAs($user)->delete(route('genres.destroy', $genre));

        $response->assertRedirect(route('genres.index'));
        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => 'Novel',
        ]);
    }
}
