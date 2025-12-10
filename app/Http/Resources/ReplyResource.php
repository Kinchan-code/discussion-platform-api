<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ReplyResource extends JsonResource
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
            'nested_replies_count' => $this->nested_replies_count ?? 0,
            'replying_to' => $this->whenLoaded('replyTo', function () {
                return $this->replyTo ? [
                    'id' => $this->replyTo->id,
                    'author' => $this->replyTo->author,
                    'body' => Str::limit($this->replyTo->body, 100),
                ] : null;
            }),
            'comment' => $this->whenLoaded('comment', fn() => new CommentResource($this->comment)),
            'created_at' => $this->created_at?->toISOString() ?? $this->created_at,
            'updated_at' => $this->updated_at?->toISOString() ?? $this->updated_at,
        ];
    }
}
