<?php

namespace App\DTOs;

class ProfileStatisticsDTO
{
    public int $total_protocols;
    public int $total_threads;
    public int $total_comments;
    public int $total_replies;
    public int $total_reviews;
    public int $total_votes_received;
    public string $member_since;
    public array $api_endpoints;
    public array $detailed_stats;

    public function __construct($user, array $statsData)
    {
        $this->total_protocols = $statsData['total_protocols'];
        $this->total_threads = $statsData['total_threads'];
        $this->total_comments = $statsData['total_comments'];
        $this->total_replies = $statsData['total_replies'];
        $this->total_reviews = $statsData['total_reviews'];
        $this->total_votes_received = $statsData['total_votes_received'];
        $this->member_since = $user->created_at?->toISOString() ?? '';

        $this->api_endpoints = [
            'description' => 'Use these endpoints to get user content with pagination and sorting',
            'protocols' => "/api/protocols?author={$user->name}",
            'threads' => "/api/threads?author={$user->name}",
            'reviews' => "/api/protocols/1/reviews?author={$user->name}",
            'replies' => "/api/profile/replies",
        ];

        $this->detailed_stats = $statsData['detailed_stats'];
    }

    public function toArray(): array
    {
        return [
            'total_protocols' => $this->total_protocols,
            'total_threads' => $this->total_threads,
            'total_comments' => $this->total_comments,
            'total_replies' => $this->total_replies,
            'total_reviews' => $this->total_reviews,
            'total_votes_received' => $this->total_votes_received,
            'member_since' => $this->member_since,
            'detailed_stats' => $this->detailed_stats,
        ];
    }
}
