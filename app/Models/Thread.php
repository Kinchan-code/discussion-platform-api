<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property int $protocol_id
 * @property string $title
 * @property string $body
 * @property string $author
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read int $upvotes
 * @property-read int $downvotes
 * @property-read int $vote_score
 * @property-read \App\Models\Protocol $protocol
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Comment[] $comments
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Vote[] $votes
 */
class Thread extends Model
{
    use HasFactory, Searchable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'protocol_id',
        'title',
        'body',
        'author',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = [
        // Removed upvotes, downvotes, vote_score to prevent N+1 queries
        // These are now handled via withCount in services
    ];


    /**
     * Get the index name for the model used by Laravel Scout.
     *
     * @return string
     */
    public function searchableAs(): string
    {
        return 'threads_index';
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
            'body' => $this->body,
            'protocol_id' => (string) $this->protocol_id,
            'author' => $this->author,
            'vote_score' => $this->vote_score,
            'created_at' => $this->created_at->timestamp,
        ];
    }


    /**
     * Get the protocol that the thread belongs to.
     *
     * @return BelongsTo
     */
    public function protocol(): BelongsTo
    {
        return $this->belongsTo(Protocol::class);
    }


    /**
     * Get the comments for the thread.
     *
     * @return HasMany
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get the votes for the thread.
     */
    public function votes(): MorphMany
    {
        return $this->morphMany(Vote::class, 'votable');
    }

    /**
     * Get the upvotes for the thread.
     */
    public function getUpvotesAttribute(): int
    {
        return $this->votes()->where('type', 'upvote')->count();
    }

    /**
     * Get the downvotes for the thread.
     */
    public function getDownvotesAttribute(): int
    {
        return $this->votes()->where('type', 'downvote')->count();
    }

    /**
     * Get the vote score (upvotes - downvotes).
     */
    public function getVoteScoreAttribute(): int
    {
        return $this->getUpvotesAttribute() - $this->getDownvotesAttribute();
    }

    /**
     * Get upvote count with caching for better performance.
     */
    public function upvotesCount(): int
    {
        return $this->votes()->where('type', 'upvote')->count();
    }

    /**
     * Get downvote count with caching for better performance.
     */
    public function downvotesCount(): int
    {
        return $this->votes()->where('type', 'downvote')->count();
    }
}
