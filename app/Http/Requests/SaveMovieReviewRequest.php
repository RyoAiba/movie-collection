<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveMovieReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'review' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => '評価を選択してください。',
            'rating.between' => '評価は1〜5で選択してください。',
            'review.max' => 'コメントは5000文字以内で入力してください。',
        ];
    }
}
