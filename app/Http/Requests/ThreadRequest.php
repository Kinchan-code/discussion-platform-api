<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ThreadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [];

        // For store operations, require protocol_id, title, and body
        if ($this->isMethod('post')) {
            $rules['protocol_id'] = 'required|exists:protocols,id';
            $rules['title'] = 'required|string|max:255';
            $rules['body'] = 'required|string';
        }

        // For update operations, make fields optional
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['protocol_id'] = 'sometimes|required|exists:protocols,id';
            $rules['title'] = 'sometimes|required|string|max:255';
            $rules['body'] = 'sometimes|required|string';
        }

        return $rules;
    }
}

