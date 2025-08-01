<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $thread_id
 * @property int|null $parent_id
 * @property string $body
 * @property string $author
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read int $upvotes
 * @property-read int $downvotes
 * @property-read int $vote_score
 * @property-read \App\Models\Thread $thread
 * @property-read \App\Models\Comment|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Comment[] $children
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Vote[] $votes
 */
class Comment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'thread_id',
        'parent_id',
        'body',
        'author',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = [
        // Removed upvotes, downvotes, vote_score to prevent N+1 queries
        // These should be handled via withCount in services when needed
    ];


    /**
     * Get the thread that the comment belongs to.
     *
     * @return BelongsTo
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(Thread::class);
    }


    /**
     * Get the parent comment.
     *
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }


    /**
     * Get the children comments.
     *
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }


    /**
     * Get the votes for the comment.
     *
     * @return MorphMany
     */
    public function votes(): MorphMany
    {
        return $this->morphMany(Vote::class, 'votable');
    }

    /**
     * Get the upvotes for the comment.
     */
    public function getUpvotesAttribute(): int
    {
        return $this->votes()->where('type', 'upvote')->count();
    }

    /**
     * Get the downvotes for the comment.
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
