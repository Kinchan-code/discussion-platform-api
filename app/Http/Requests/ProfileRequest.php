<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user?->id),
            ],
            'current_password' => ['sometimes', 'required', 'string'],
            'new_password' => ['sometimes', 'required', 'string', 'min:8'],
            'new_password_confirmation' => ['sometimes', 'required_with:new_password', 'same:new_password'],
        ];
    }
}
