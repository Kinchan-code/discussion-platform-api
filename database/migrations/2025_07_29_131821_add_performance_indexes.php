<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add indexes for comments table
        Schema::table('comments', function (Blueprint $table) {
            $table->index(['thread_id', 'parent_id'], 'idx_comments_thread_parent');
            $table->index('author', 'idx_comments_author');
            $table->index('created_at', 'idx_comments_created_at');
        });

        // Add indexes for votes table
        Schema::table('votes', function (Blueprint $table) {
            $table->index(['votable_type', 'votable_id', 'type'], 'idx_votes_votable_type');
            $table->index(['votable_type', 'votable_id', 'user_id'], 'idx_votes_user');
        });

        // Add indexes for threads table
        Schema::table('threads', function (Blueprint $table) {
            $table->index('protocol_id', 'idx_threads_protocol');
            $table->index('author', 'idx_threads_author');
            $table->index('created_at', 'idx_threads_created_at');
        });

        // Add indexes for protocols table
        Schema::table('protocols', function (Blueprint $table) {
            $table->index('author', 'idx_protocols_author');
            $table->index('created_at', 'idx_protocols_created_at');
        });

        // Add indexes for reviews table
        Schema::table('reviews', function (Blueprint $table) {
            $table->index('protocol_id', 'idx_reviews_protocol');
            $table->index('author', 'idx_reviews_author');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes for comments table
        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('idx_comments_thread_parent');
            $table->dropIndex('idx_comments_author');
            $table->dropIndex('idx_comments_created_at');
        });

        // Drop indexes for votes table
        Schema::table('votes', function (Blueprint $table) {
            $table->dropIndex('idx_votes_votable_type');
            $table->dropIndex('idx_votes_user');
        });

        // Drop indexes for threads table
        Schema::table('threads', function (Blueprint $table) {
            $table->dropIndex('idx_threads_protocol');
            $table->dropIndex('idx_threads_author');
            $table->dropIndex('idx_threads_created_at');
        });

        // Drop indexes for protocols table
        Schema::table('protocols', function (Blueprint $table) {
            $table->dropIndex('idx_protocols_author');
            $table->dropIndex('idx_protocols_created_at');
        });

        // Drop indexes for reviews table
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('idx_reviews_protocol');
            $table->dropIndex('idx_reviews_author');
        });
    }
};
