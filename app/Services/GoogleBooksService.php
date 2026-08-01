<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GoogleBooksService
{
    /**
     * @return array<string, string|null>|null
     */
    public function searchByIsbn(string $isbn): ?array
    {
        $query = [
            'q' => "isbn:{$isbn}",
            'maxResults' => 1,
            'printType' => 'books',
        ];

        $apiKey = config('services.google_books.api_key');

        if ($apiKey) {
            $query['key'] = $apiKey;
        }

        $response = Http::acceptJson()
            ->timeout(10)
            ->get(
                config('services.google_books.base_url').'/volumes',
                $query
            );

        $response->throw();

        $volumeInfo = $response->json('items.0.volumeInfo');

        if (! is_array($volumeInfo)) {
            return null;
        }

        return [
            'title' => $volumeInfo['title'] ?? null,
            'author' => $this->formatAuthors(
                $volumeInfo['authors'] ?? null
            ),
            'description' => $volumeInfo['description'] ?? null,
            'image_url' => $this->formatImageUrl(
                $volumeInfo['imageLinks']['thumbnail'] ?? null
            ),
            'published_date' => $this->formatPublishedDate(
                $volumeInfo['publishedDate'] ?? null
            ),
        ];
    }

    /**
     * @param  array<int, string>|null  $authors
     */
    private function formatAuthors(?array $authors): ?string
    {
        return $authors ? implode(', ', $authors) : null;
    }

    private function formatImageUrl(?string $imageUrl): ?string
    {
        return $imageUrl
            ? preg_replace('/^http:/', 'https:', $imageUrl)
            : null;
    }

    private function formatPublishedDate(?string $publishedDate): ?string
    {
        if (! $publishedDate) {
            return null;
        }

        return match (strlen($publishedDate)) {
            4 => "{$publishedDate}-01-01",
            7 => "{$publishedDate}-01",
            default => substr($publishedDate, 0, 10),
        };
    }
}
