<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProtocolResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Use getRelationValue instead of property access due to belongsToMany eager loading issue
        $tags = $this->getRelationValue('tags') ?? $this->resource->tags()->get();
        
        return [
            'id' => $this->id,
            'title' => $this->title ?? '',
            'content' => $this->content ?? '',
            'tags' => $tags && $tags->isNotEmpty() 
                ? TagResource::collection($tags)->toArray($request) 
                : [],
            'author' => $this->author ?? '',
            'reviews_count' => $this->reviews_count ?? null,
            'threads_count' => $this->threads_count ?? null,
            'reviews_avg_rating' => $this->reviews_avg_rating ? round($this->reviews_avg_rating, 2) : null,
            'created_at' => $this->created_at?->toISOString() ?? $this->created_at,
            'updated_at' => $this->updated_at?->toISOString() ?? $this->updated_at,
        ];
    }
}
