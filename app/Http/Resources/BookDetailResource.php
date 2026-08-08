<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn,
            'published_date' => $this->published_date?->format('Y-m-d'),
            'description' => $this->description,
            'image_url' => $this->image_url,
            'genres' => GenreResource::collection(
                $this->whenLoaded('genres')
            ),
            'reviews_avg_rating' => $this->when(
                isset($this->reviews_avg_rating),
                $this->reviews_avg_rating
            ),
            'reviews_count' => $this->when(
                isset($this->reviews_count),
                $this->reviews_count
            ),
            'reviews' => ReviewResource::collection(
                $this->whenLoaded('reviews')
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
