<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProtocolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'tags' => 'nullable|array',
        ];

        // For store operations, require title and content
        if ($this->isMethod('post')) {
            $rules['title'] = 'required|string|max:255';
            $rules['content'] = 'required|string';
        }

        // For update operations, make title and content optional
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['title'] = 'sometimes|required|string|max:255';
            $rules['content'] = 'sometimes|required|string';
        }

        return $rules;
    }
}

