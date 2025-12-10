<?php

namespace App\Enums;

enum VoteType: string
{
    case UPVOTE = 'upvote';
    case DOWNVOTE = 'downvote';

    /**
     * Get the numeric value for the vote type.
     * upvote = 1, downvote = -1
     *
     * @return int
     */
    public function value(): int
    {
        return match ($this) {
            self::UPVOTE => 1,
            self::DOWNVOTE => -1,
        };
    }

    /**
     * Get the numeric value from a vote type string.
     *
     * @param string $type
     * @return int
     */
    public static function toValue(string $type): int
    {
        return match ($type) {
            'upvote' => 1,
            'downvote' => -1,
            default => 0,
        };
    }

    /**
     * Get vote type from numeric value.
     *
     * @param int $value
     * @return self|null
     */
    public static function fromValue(int $value): ?self
    {
        return match ($value) {
            1 => self::UPVOTE,
            -1 => self::DOWNVOTE,
            default => null,
        };
    }

    /**
     * Get all vote type values as array for SQL CASE statements.
     *
     * @return array<string, int>
     */
    public static function sqlCaseMapping(): array
    {
        return [
            'upvote' => 1,
            'downvote' => -1,
        ];
    }

    /**
     * Get SQL CASE expression for vote score calculation.
     *
     * @return string
     */
    public static function sqlCaseExpression(): string
    {
        return "CASE WHEN votes.type = 'upvote' THEN 1 WHEN votes.type = 'downvote' THEN -1 ELSE 0 END";
    }
}

