<?php

namespace App\DTOs;

use Illuminate\Support\Str;

class ProfileReplyDTO
{
    public int $id;
    public string $body;
    public string $author;
    public int $thread_id;
    public int $parent_id;
    public int $upvotes;
    public int $downvotes;
    public int $vote_score;
    public int $nested_replies_count;
    public string $created_at;
    public string $updated_at;
    public array $thread;
    public array $reply_context;
    public array $navigation;

    public function __construct($reply)
    {
        $upvotes = $reply->votes()->where('type', 'upvote')->count();
        $downvotes = $reply->votes()->where('type', 'downvote')->count();
        $isNestedReply = $reply->parent && $reply->parent->parent_id !== null;

        $this->id = $reply->id;
        $this->body = $reply->body;
        $this->author = $reply->author;
        $this->thread_id = $reply->thread_id;
        $this->parent_id = $reply->parent_id;
        $this->upvotes = $upvotes;
        $this->downvotes = $downvotes;
        $this->vote_score = $upvotes - $downvotes;
        $this->nested_replies_count = $reply->nested_replies_count ?? 0;
        $this->created_at = $reply->created_at?->toISOString() ?? '';
        $this->updated_at = $reply->updated_at?->toISOString() ?? '';

        $this->thread = [
            'id' => $reply->thread->id,
            'title' => $reply->thread->title,
        ];

        $this->reply_context = [
            'is_nested_reply' => $isNestedReply,
            'replying_to_author' => $reply->parent ? $reply->parent->author : null,
            'replying_to_excerpt' => $reply->parent ? Str::limit($reply->parent->body, 100) : null,
            'original_comment_id' => $isNestedReply ? $reply->parent->parent_id : $reply->parent_id,
        ];

        $this->navigation = [
            'thread_url' => "/threads/{$reply->thread_id}",
            'highlight_url' => "/threads/{$reply->thread_id}?highlight_reply={$reply->id}",
            'api_url' => "/api/threads/{$reply->thread_id}/comments?highlight_reply={$reply->id}",
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
            'nested_replies_count' => $this->nested_replies_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'thread' => $this->thread,
            'reply_context' => $this->reply_context,
            'navigation' => $this->navigation,
        ];
    }
}
