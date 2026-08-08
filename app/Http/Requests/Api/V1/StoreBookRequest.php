<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('genre_ids') && is_array($this->genre_ids)) {
            $this->merge([
                'genre_ids' => array_values(array_unique($this->genre_ids)),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => ['required', 'digits:13', 'unique:books,isbn'],
            'published_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'genre_ids' => ['required', 'array', 'min:1', 'max:3'],
            'genre_ids.*' => ['integer', 'exists:genres,id'],
        ];
    }
}
