<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_reading_report(): void
    {
        $response = $this->get(route('reports.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_report_handles_user_with_no_reviews(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('reports.index'));

        $response
            ->assertOk()
            ->assertViewIs('reports.index')
            ->assertViewHas('stats', function (array $stats): bool {
                return $stats['summary']['total_reviews'] === 0
                    && $stats['summary']['books_read'] === 0
                    && $stats['summary']['average_rating'] === 0.0
                    && $stats['rating_distribution']->all() === [0, 0, 0, 0, 0]
                    && $stats['top_rated_books']->isEmpty()
                    && $stats['genre_ratings']->isEmpty();
            })
            ->assertSeeText('0.0');
    }

    public function test_summary_uses_only_authenticated_users_reviews(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $firstBook = Book::create([
            'user_id' => $user->id,
            'title' => 'Laravel入門',
            'author' => '山田太郎',
            'isbn' => '9781234567890',
            'published_date' => '2025-01-01',
        ]);

        $secondBook = Book::create([
            'user_id' => $user->id,
            'title' => 'PHP実践',
            'author' => '鈴木花子',
            'isbn' => '9781234567891',
            'published_date' => '2025-02-01',
        ]);

        $otherBook = Book::create([
            'user_id' => $otherUser->id,
            'title' => '他ユーザーの書籍',
            'author' => '田中一郎',
            'isbn' => '9781234567892',
            'published_date' => '2025-03-01',
        ]);

        Review::create([
            'user_id' => $user->id,
            'book_id' => $firstBook->id,
            'rating' => 5,
            'body' => 'とても良かったです。',
        ]);

        Review::create([
            'user_id' => $user->id,
            'book_id' => $secondBook->id,
            'rating' => 3,
            'body' => '参考になりました。',
        ]);

        Review::create([
            'user_id' => $otherUser->id,
            'book_id' => $otherBook->id,
            'rating' => 1,
            'body' => '他ユーザーのレビューです。',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reports.index'));

        $response
            ->assertOk()
            ->assertViewHas('stats', function (array $stats): bool {
                return $stats['summary']['total_reviews'] === 2
                    && $stats['summary']['books_read'] === 2
                    && $stats['summary']['average_rating'] === 4.0;
            });
    }

    public function test_rating_distribution_contains_all_ratings(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        collect([1, 3, 3, 5])->each(
            function (int $rating, int $index) use ($user): void {
                $book = Book::create([
                    'user_id' => $user->id,
                    'title' => "評価テスト書籍{$index}",
                    'author' => 'テスト著者',
                    'isbn' => sprintf('97812345678%02d', $index),
                    'published_date' => '2025-01-01',
                ]);

                Review::create([
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                    'rating' => $rating,
                    'body' => '評価分布のテストです。',
                ]);
            }
        );

        $otherBook = Book::create([
            'user_id' => $otherUser->id,
            'title' => '他ユーザーの評価テスト書籍',
            'author' => '別の著者',
            'isbn' => '9781234567899',
            'published_date' => '2025-01-01',
        ]);

        Review::create([
            'user_id' => $otherUser->id,
            'book_id' => $otherBook->id,
            'rating' => 4,
            'body' => '集計対象外です。',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reports.index'));

        $response
            ->assertOk()
            ->assertViewHas(
                'stats',
                fn (array $stats): bool => $stats['rating_distribution']->all() === [1, 0, 2, 0, 1]
            );
    }

    public function test_top_rated_books_are_ordered_and_limited_to_five(): void
    {
        $user = User::factory()->create();

        collect([5, 4, 5, 3, 4, 5, 4])->each(
            function (int $rating, int $index) use ($user): void {
                $book = Book::create([
                    'user_id' => $user->id,
                    'title' => "高評価テスト書籍{$index}",
                    'author' => "テスト著者{$index}",
                    'isbn' => sprintf('9781234567%03d', $index),
                    'published_date' => '2025-01-01',
                ]);

                Review::create([
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                    'rating' => $rating,
                    'body' => '高評価TOP5のテストです。',
                ]);
            }
        );

        $response = $this
            ->actingAs($user)
            ->get(route('reports.index'));

        $response
            ->assertOk()
            ->assertViewHas('stats', function (array $stats): bool {
                $topRatedBooks = $stats['top_rated_books'];

                return $topRatedBooks->count() === 5
                    && $topRatedBooks->pluck('rating')->all() === [5, 5, 5, 4, 4]
                    && $topRatedBooks->every(
                        fn (array $book): bool => $book['rating'] >= 4
                    );
            });
    }

    public function test_top_rated_books_use_updated_at_when_ratings_are_equal(): void
    {
        $user = User::factory()->create();

        $olderBook = Book::create([
            'user_id' => $user->id,
            'title' => '更新日時が古い書籍',
            'author' => '古い著者',
            'isbn' => '9781234567900',
            'published_date' => '2025-01-01',
        ]);

        $newerBook = Book::create([
            'user_id' => $user->id,
            'title' => '更新日時が新しい書籍',
            'author' => '新しい著者',
            'isbn' => '9781234567901',
            'published_date' => '2025-01-01',
        ]);

        $olderReview = Review::create([
            'user_id' => $user->id,
            'book_id' => $olderBook->id,
            'rating' => 5,
            'body' => '古いレビューです。',
        ]);

        $newerReview = Review::create([
            'user_id' => $user->id,
            'book_id' => $newerBook->id,
            'rating' => 5,
            'body' => '新しいレビューです。',
        ]);

        Review::query()
            ->whereKey($olderReview->id)
            ->update([
                'updated_at' => now()->subDay(),
            ]);

        Review::query()
            ->whereKey($newerReview->id)
            ->update([
                'updated_at' => now(),
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reports.index'));

        $response
            ->assertOk()
            ->assertViewHas('stats', function (array $stats) use ($newerBook, $olderBook): bool {
                return $stats['top_rated_books']->pluck('id')->all() === [
                    $newerBook->id,
                    $olderBook->id,
                ];
            });
    }

    public function test_genre_ratings_are_calculated_and_limited_to_five(): void
    {
        $user = User::factory()->create();

        $genreData = [
            ['name' => 'ジャンルA', 'rating' => 5],
            ['name' => 'ジャンルB', 'rating' => 4],
            ['name' => 'ジャンルC', 'rating' => 3],
            ['name' => 'ジャンルD', 'rating' => 2],
            ['name' => 'ジャンルE', 'rating' => 1],
            ['name' => 'ジャンルF', 'rating' => 5],
        ];

        collect($genreData)->each(
            function (array $data, int $index) use ($user): void {
                $genre = Genre::create([
                    'name' => $data['name'],
                ]);

                $book = Book::create([
                    'user_id' => $user->id,
                    'title' => "ジャンル集計テスト書籍{$index}",
                    'author' => 'テスト著者',
                    'isbn' => sprintf('9781234568%03d', $index),
                    'published_date' => '2025-01-01',
                ]);

                $book->genres()->attach($genre->id);

                Review::create([
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                    'rating' => $data['rating'],
                    'body' => 'ジャンル別評価傾向のテストです。',
                ]);
            }
        );

        $response = $this
            ->actingAs($user)
            ->get(route('reports.index'));

        $response
            ->assertOk()
            ->assertViewHas('stats', function (array $stats): bool {
                $genreRatings = $stats['genre_ratings'];

                return $genreRatings->count() === 5
                    && $genreRatings->pluck('average_rating')->all() === [
                        5.0,
                        5.0,
                        4.0,
                        3.0,
                        2.0,
                    ]
                    && $genreRatings->every(
                        fn (array $genre): bool => $genre['count'] === 1
                    );
            });
    }

    public function test_reviews_are_counted_in_each_assigned_genre(): void
    {
        $user = User::factory()->create();

        $firstGenre = Genre::create([
            'name' => 'プログラミング',
        ]);

        $secondGenre = Genre::create([
            'name' => 'Web開発',
        ]);

        $firstBook = Book::create([
            'user_id' => $user->id,
            'title' => '複数ジャンルの書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567910',
            'published_date' => '2025-01-01',
        ]);

        $secondBook = Book::create([
            'user_id' => $user->id,
            'title' => '単一ジャンルの書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567911',
            'published_date' => '2025-01-01',
        ]);

        $firstBook->genres()->attach([
            $firstGenre->id,
            $secondGenre->id,
        ]);

        $secondBook->genres()->attach($firstGenre->id);

        Review::create([
            'user_id' => $user->id,
            'book_id' => $firstBook->id,
            'rating' => 5,
            'body' => '複数ジャンルへ集計されるレビューです。',
        ]);

        Review::create([
            'user_id' => $user->id,
            'book_id' => $secondBook->id,
            'rating' => 3,
            'body' => '単一ジャンルのレビューです。',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reports.index'));

        $response
            ->assertOk()
            ->assertViewHas('stats', function (array $stats) use ($firstGenre, $secondGenre): bool {
                $genreRatings = $stats['genre_ratings']->keyBy('id');

                return $genreRatings[$firstGenre->id]['count'] === 2
                    && $genreRatings[$firstGenre->id]['average_rating'] === 4.0
                    && $genreRatings[$secondGenre->id]['count'] === 1
                    && $genreRatings[$secondGenre->id]['average_rating'] === 5.0;
            });
    }

    public function test_genre_ratings_use_review_count_when_averages_are_equal(): void
    {
        $user = User::factory()->create();

        $moreReviewsGenre = Genre::create([
            'name' => 'レビュー件数が多いジャンル',
        ]);

        $fewerReviewsGenre = Genre::create([
            'name' => 'レビュー件数が少ないジャンル',
        ]);

        $firstBook = Book::create([
            'user_id' => $user->id,
            'title' => '評価5の書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567920',
            'published_date' => '2025-01-01',
        ]);

        $secondBook = Book::create([
            'user_id' => $user->id,
            'title' => '評価3の書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567921',
            'published_date' => '2025-01-01',
        ]);

        $thirdBook = Book::create([
            'user_id' => $user->id,
            'title' => '評価4の書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567922',
            'published_date' => '2025-01-01',
        ]);

        $firstBook->genres()->attach($moreReviewsGenre->id);
        $secondBook->genres()->attach($moreReviewsGenre->id);
        $thirdBook->genres()->attach($fewerReviewsGenre->id);

        Review::create([
            'user_id' => $user->id,
            'book_id' => $firstBook->id,
            'rating' => 5,
            'body' => '評価5です。',
        ]);

        Review::create([
            'user_id' => $user->id,
            'book_id' => $secondBook->id,
            'rating' => 3,
            'body' => '評価3です。',
        ]);

        Review::create([
            'user_id' => $user->id,
            'book_id' => $thirdBook->id,
            'rating' => 4,
            'body' => '評価4です。',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reports.index'));

        $response
            ->assertOk()
            ->assertViewHas('stats', function (array $stats) use (
                $moreReviewsGenre,
                $fewerReviewsGenre
            ): bool {
                return $stats['genre_ratings']->pluck('id')->all() === [
                    $moreReviewsGenre->id,
                    $fewerReviewsGenre->id,
                ];
            });
    }
}
