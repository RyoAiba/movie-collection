<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMovieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tmdb_id' => ['required', 'integer', 'min:1', 'unique:movies,tmdb_id'],
        ];
    }

    public function messages(): array
    {
        return [
            'tmdb_id.unique' => 'この映画はすでにコレクションに追加されています。',
        ];
    }
}
