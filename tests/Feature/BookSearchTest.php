<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_books_can_be_searched_by_title(): void
    {
        $user = User::factory()->create();

        $this->createBook($user, 'Laravel入門', '山田太郎');
        $this->createBook($user, 'PHP基礎', '佐藤花子');

        $response = $this->get(route('books.index', [
            'keyword' => 'Laravel',
        ]));

        $response->assertOk();
        $response->assertSee('Laravel入門');
        $response->assertDontSee('PHP基礎');
    }

    public function test_books_can_be_searched_by_author(): void
    {
        $user = User::factory()->create();

        $this->createBook($user, 'Laravel入門', '山田太郎');
        $this->createBook($user, 'PHP基礎', '佐藤花子');

        $response = $this->get(route('books.index', [
            'keyword' => '佐藤',
        ]));

        $response->assertOk();
        $response->assertSee('PHP基礎');
        $response->assertDontSee('Laravel入門');
    }

    public function test_books_can_be_filtered_by_genre(): void
    {
        $user = User::factory()->create();
        $novel = Genre::create(['name' => '小説']);
        $business = Genre::create(['name' => 'ビジネス']);

        $novelBook = $this->createBook($user, '小説の本', '著者A');
        $businessBook = $this->createBook($user, '仕事の本', '著者B');

        $novelBook->genres()->attach($novel);
        $businessBook->genres()->attach($business);

        $response = $this->get(route('books.index', [
            'genre_id' => $novel->id,
        ]));

        $response->assertOk();
        $response->assertSee('小説の本');
        $response->assertDontSee('仕事の本');
    }

    public function test_books_can_be_sorted_by_title(): void
    {
        $user = User::factory()->create();

        $this->createBook($user, 'Bの本', '著者B');
        $this->createBook($user, 'Aの本', '著者A');

        $response = $this->get(route('books.index', [
            'sort' => 'title',
        ]));

        $response->assertOk();
        $response->assertSeeInOrder([
            'Aの本',
            'Bの本',
        ]);
    }

    public function test_books_can_be_sorted_by_average_rating(): void
    {
        $user = User::factory()->create();

        $highRatedBook = $this->createBook($user, '高評価の本', '著者A');
        $lowRatedBook = $this->createBook($user, '低評価の本', '著者B');

        Review::create([
            'user_id' => $user->id,
            'book_id' => $highRatedBook->id,
            'rating' => 5,
            'body' => '高評価です。',
        ]);

        Review::create([
            'user_id' => User::factory()->create()->id,
            'book_id' => $lowRatedBook->id,
            'rating' => 2,
            'body' => '低評価です。',
        ]);

        $response = $this->get(route('books.index', [
            'sort' => 'rating',
        ]));

        $response->assertOk();
        $response->assertSeeInOrder([
            '高評価の本',
            '低評価の本',
        ]);
    }

    private function createBook(
        User $user,
        string $title,
        string $author
    ): Book {
        return Book::create([
            'user_id' => $user->id,
            'title' => $title,
            'author' => $author,
            'isbn' => null,
            'published_date' => null,
        ]);
    }
}
