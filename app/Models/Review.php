<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property int $id
 * @property int $protocol_id
 * @property int $rating
 * @property string|null $feedback
 * @property string $author
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\Protocol $protocol
 * @property-read int $helpful_count
 * @property-read int $not_helpful_count
 */
class Review extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'protocol_id',
        'rating',
        'feedback',
        'author',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'rating' => 'integer',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = [
        'helpful_count',
        'not_helpful_count',
    ];


    /**
     * Get the protocol that the review belongs to.
     *
     * @return BelongsTo
     */
    public function protocol(): BelongsTo
    {
        return $this->belongsTo(Protocol::class);
    }


    /**
     * Get all votes for the review.
     *
     * @return MorphMany
     */
    public function votes(): MorphMany
    {
        return $this->morphMany(Vote::class, 'votable');
    }


    /**
     * Accessor for the helpful count for the review.
     *
     * @return int
     */
    public function getHelpfulCountAttribute(): int
    {
        return $this->votes()->where('type', 'upvote')->count();
    }


    /**
     * Accessor for the not helpful count for the review.
     *
     * @return int
     */
    public function getNotHelpfulCountAttribute(): int
    {
        return $this->votes()->where('type', 'downvote')->count();
    }

    /**
     * Check if the current user has voted helpful on this review.
     */
    public function hasUserVotedHelpful($userId): bool
    {
        return $this->votes()->where('type', 'upvote')->where('user_id', $userId)->exists();
    }

    /**
     * Check if the current user has voted not helpful on this review.
     */
    public function hasUserVotedNotHelpful($userId): bool
    {
        return $this->votes()->where('type', 'downvote')->where('user_id', $userId)->exists();
    }
}
