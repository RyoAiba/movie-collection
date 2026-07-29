<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMovieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'review' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.between' => '評価は1〜5で選択してください。',
            'review.max' => 'レビューは5000文字以内で入力してください。',
        ];
    }
}
