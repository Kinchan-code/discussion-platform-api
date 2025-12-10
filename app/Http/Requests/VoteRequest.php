<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'votable_id' => 'required|integer',
            'votable_type' => 'required|in:thread,comment,reply,review',
            'vote_type' => 'required|in:upvote,downvote',
        ];
    }
}

