<?php

namespace App\DTOs;

use Illuminate\Support\Str;

class ProfileCommentDTO
{
    public int $id;
    public string $body;
    public string $author;
    public int $thread_id;
    public ?int $parent_id;
    public int $upvotes;
    public int $downvotes;
    public int $vote_score;
    public int $replies_count;
    public string $created_at;
    public string $updated_at;
    public array $thread;
    public array $engagement;
    public array $navigation;

    public function __construct($comment)
    {
        $upvotes = $comment->votes()->where('type', 'upvote')->count();
        $downvotes = $comment->votes()->where('type', 'downvote')->count();

        $this->id = $comment->id;
        $this->body = $comment->body;
        $this->author = $comment->author;
        $this->thread_id = $comment->thread_id;
        $this->parent_id = $comment->parent_id;
        $this->upvotes = $upvotes;
        $this->downvotes = $downvotes;
        $this->vote_score = $upvotes - $downvotes;
        $this->replies_count = $comment->children()->count();
        $this->created_at = $comment->created_at?->toISOString() ?? '';
        $this->updated_at = $comment->updated_at?->toISOString() ?? '';

        $this->thread = [
            'id' => $comment->thread->id,
            'title' => $comment->thread->title,
            'protocol_id' => $comment->thread->protocol_id,
        ];

        $this->engagement = [
            'total_votes' => $upvotes + $downvotes,
            'vote_ratio' => $upvotes + $downvotes > 0 ? round($upvotes / ($upvotes + $downvotes) * 100, 1) : 0,
            'has_replies' => $this->replies_count > 0,
            'activity_level' => $this->getActivityLevel($upvotes + $downvotes, $this->replies_count),
        ];

        $this->navigation = [
            'comment_url' => "/api/threads/{$comment->thread_id}/comments?highlight_comment={$comment->id}",
            'thread_url' => "/api/threads/{$comment->thread_id}",
            'replies_url' => $this->replies_count > 0 ? "/api/comments/{$comment->id}/replies" : null,
        ];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'author' => $this->author,
            'thread_id' => $this->thread_id,
            'parent_id' => $this->parent_id,
            'upvotes' => $this->upvotes,
            'downvotes' => $this->downvotes,
            'vote_score' => $this->vote_score,
            'replies_count' => $this->replies_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'thread' => $this->thread,
            'engagement' => $this->engagement,
            'navigation' => $this->navigation,
        ];
    }

    private function getActivityLevel(int $totalVotes, int $repliesCount): string
    {
        $totalEngagement = $totalVotes + ($repliesCount * 2); // Weight replies more

        if ($totalEngagement >= 10) return 'high';
        if ($totalEngagement >= 5) return 'medium';
        if ($totalEngagement >= 1) return 'low';
        return 'none';
    }
}
