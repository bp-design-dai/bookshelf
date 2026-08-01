<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleBooksIsbnSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_search_book_by_isbn(): void
    {
        $response = $this->get(
            route('books.isbn.search', [
                'isbn' => '9784101010014',
            ])
        );

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_search_book_by_isbn(): void
    {
        Http::fake([
            '*' => Http::response([
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => '吾輩は猫である',
                            'authors' => ['夏目漱石'],
                            'description' => 'テスト説明文',
                            'imageLinks' => [
                                'thumbnail' => 'http://example.com/book.jpg',
                            ],
                            'publishedDate' => '1980',
                        ],
                    ],
                ],
            ]),
        ]);

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->getJson(route('books.isbn.search', [
                'isbn' => '9784101010014',
            ]));

        $response->assertOk();
        $response->assertExactJson([
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'description' => 'テスト説明文',
            'image_url' => 'https://example.com/book.jpg',
            'published_date' => '1980-01-01',
        ]);
    }

    public function test_missing_book_returns_404(): void
    {
        Http::fake([
            '*' => Http::response([
                'totalItems' => 0,
            ]),
        ]);

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->getJson(route('books.isbn.search', [
                'isbn' => '9999999999999',
            ]));

        $response->assertNotFound();
        $response->assertJson([
            'error' => '書籍が見つかりませんでした。',
        ]);
    }

    public function test_isbn_must_be_13_digits(): void
    {
        Http::fake();

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->getJson(route('books.isbn.search', [
                'isbn' => 'abcdefghijklm',
            ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('isbn');

        Http::assertNothingSent();
    }

    public function test_google_books_api_failure_returns_502(): void
    {
        Http::fake([
            '*' => Http::response([], 500),
        ]);

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->getJson(route('books.isbn.search', [
                'isbn' => '9784101010014',
            ]));

        $response->assertStatus(502);
        $response->assertJson([
            'error' => '書籍情報の取得に失敗しました。',
        ]);
    }
}
