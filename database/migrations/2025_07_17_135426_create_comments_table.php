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
        Schema::create('comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('thread_id')->constrained()->onDelete('cascade');
            $table->text('body');
            $table->string('author');
            $table->timestamps();

            $table->index('thread_id', 'idx_comments_thread');
            $table->index('author', 'idx_comments_author');
            $table->index('created_at', 'idx_comments_created_at');
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
