<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('votes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable(); // optional, for future extension
            $table->string('votable_type');
            $table->uuid('votable_id');
            $table->enum('type', ['upvote', 'downvote']);
            $table->timestamps();
    
            $table->unique(['user_id', 'votable_type', 'votable_id'], 'unique_vote');
            $table->index(['votable_type', 'votable_id', 'type'], 'idx_votes_votable_type');
            $table->index(['votable_type', 'votable_id', 'user_id'], 'idx_votes_user');
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
