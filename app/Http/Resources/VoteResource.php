<?php

namespace App\Http\Resources;

use App\Models\Thread;
use App\Models\Comment;
use App\Models\Reply;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'votable_id' => $this->votable_id,
            'votable_type' => match ($this->votable_type) {
                Thread::class => 'thread',
                Comment::class => 'comment',
                Reply::class => 'reply',
                default => $this->votable_type,
            },
            'vote_type' => $this->type,
            'user' => $this->whenLoaded('user', fn() => new UserResource($this->user)),
            'created_at' => $this->created_at?->toISOString() ?? $this->created_at,
            'updated_at' => $this->updated_at?->toISOString() ?? $this->updated_at,
        ];
    }
}
