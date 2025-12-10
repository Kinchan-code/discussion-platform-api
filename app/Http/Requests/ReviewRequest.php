<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // For store operations, require rating
        if ($this->isMethod('post')) {
            return [
                'rating' => ['required', 'integer', 'min:1', 'max:5'],
                'feedback' => ['nullable', 'string'],
            ];
        }

        // For update operations, make rating optional
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            return [
                'rating' => ['sometimes', 'required', 'integer', 'min:1', 'max:5'],
                'feedback' => ['sometimes', 'nullable', 'string'],
            ];
        }

        return [];
    }
}
