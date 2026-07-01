<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_book_create_page(): void
    {
        $response = $this->get(route('books.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_cannot_access_other_users_book_edit_page(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::create([
            'user_id' => $owner->id,
            'title' => '別ユーザーの本',
            'author' => '別ユーザー著者',
            'isbn' => '9784000000001',
            'published_date' => '2024-01-01',
            'description' => '認可確認用',
        ]);

        $response = $this->actingAs($otherUser)->get(route('books.edit', $book));

        $response->assertForbidden();
    }
}
