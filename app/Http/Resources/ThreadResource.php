<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ThreadResource extends JsonResource
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
            'title' => $this->title,
            'body' => $this->body,
            'author' => $this->author,
            'upvotes' => $this->upvotes ?? 0,
            'downvotes' => $this->downvotes ?? 0,
            'vote_score' => $this->vote_score ?? 0,
            'user_vote' => $this->whenLoaded('votes', function () {
                $userVote = $this->votes->first();
                if (!$userVote) {
                    return null;
                }
                return $userVote->type;
            }),
            'comments_count' => $this->comments_count ?? 0,
            'created_at' => $this->created_at?->toISOString() ?? $this->created_at,
            'updated_at' => $this->updated_at?->toISOString() ?? $this->updated_at,
            'protocol' => $this->whenLoaded('protocol', fn() => new ProtocolResource($this->protocol)),
        ];
    }
}
