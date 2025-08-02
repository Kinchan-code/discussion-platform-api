<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property string $title
 * @property string $content
 * @property array $tags
 * @property string $author
 * @property float $rating
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read float $average_rating
 * @property-read int|null $reviews_count
 * @property-read int|null $threads_count
 * @property-read float|null $reviews_avg_rating
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Thread[] $threads
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Review[] $reviews
 */
class Protocol extends Model
{
    use HasFactory, Searchable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'content',
        'tags',
        'author',
        'rating',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'tags' => 'array',
        'rating' => 'float',
    ];


    /**
     * Get the index name for the model used by Laravel Scout.
     *
     * @return string
     */
    public function searchableAs(): string
    {
        return 'protocols_index';
    }


    /**
     * Get the array representation of the model for search indexing.
     *
     * @return array
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => (string) $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'tags' => is_array($this->tags) ? implode(' ', $this->tags) : (string) $this->tags,
            'author' => $this->author,
            'rating' => (float) $this->rating,
            'reviews_count' => $this->reviews_count ?? $this->reviews()->count(),
            'threads_count' => $this->threads_count ?? $this->threads()->count(),
            'created_at' => $this->created_at?->timestamp ?? time(),
        ];
    }


    /**
     * Get the threads associated with the protocol.
     *
     * @return HasMany
     */
    public function threads(): HasMany
    {
        return $this->hasMany(Thread::class);
    }


    /**
     * Get the reviews associated with the protocol.
     *
     * @return HasMany
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }


    /**
     * Accessor for the average rating from related reviews.
     *
     * @return float
     */
    public function getAverageRatingAttribute(): float
    {
        return $this->reviews()->avg('rating') ?? 0.0;
    }


    /**
     * Accessor for the rating attribute (alias for average rating).
     *
     * @return float
     */
    public function getRatingAttribute(): float
    {
        return $this->getAverageRatingAttribute();
    }
}
