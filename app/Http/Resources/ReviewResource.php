<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ReviewResource extends JsonResource
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
            'protocol_id' => $this->protocol_id,
            'rating' => $this->rating,
            'feedback' => $this->feedback,
            'author' => $this->author,
            'helpful_count' => $this->upvotes ?? 0,
            'not_helpful_count' => $this->downvotes ?? 0,
            'user_vote' => $this->whenLoaded('votes', function () {
                $userVote = $this->votes->first();
                if (!$userVote) {
                    return null;
                }
                return $userVote->type === 'upvote' ? 'helpful' : 'not_helpful';
            }),
            'created_at' => $this->created_at?->toISOString() ?? $this->created_at,
            'updated_at' => $this->updated_at?->toISOString() ?? $this->updated_at,
            'protocol' => $this->whenLoaded('protocol', fn() => new ProtocolResource($this->protocol)),
        ];
    }
}
