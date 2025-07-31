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
            $table->id();
            $table->foreignId('user_id')->nullable(); // optional, for future extension
            $table->string('votable_type');
            $table->unsignedBigInteger('votable_id');
            $table->enum('type', ['upvote', 'downvote']);
            $table->timestamps();
    
            $table->unique(['user_id', 'votable_type', 'votable_id'], 'unique_vote');
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
