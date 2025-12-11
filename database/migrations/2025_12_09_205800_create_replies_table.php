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
        Schema::create('replies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('comment_id');
            $table->uuid('parent_id')->nullable();
            $table->uuid('reply_to_id')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->text('body');
            $table->string('author');
            $table->timestamps();

            $table->index('comment_id', 'idx_replies_comment');
            $table->index('parent_id', 'idx_replies_parent');
            $table->index('author', 'idx_replies_author');
            $table->index('created_at', 'idx_replies_created_at');
        });

        // Add foreign key constraints after table creation
        Schema::table('replies', function (Blueprint $table) {
            $table->foreign('comment_id')->references('id')->on('comments')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('replies')->onDelete('cascade');
            $table->foreign('reply_to_id')->references('id')->on('replies')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('replies');
    }
};
