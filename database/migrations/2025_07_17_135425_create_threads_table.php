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
        Schema::create('threads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('protocol_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('body');
            $table->string('author');
            $table->timestamps();

            $table->index('protocol_id', 'idx_threads_protocol');
            $table->index('author', 'idx_threads_author');
            $table->index('created_at', 'idx_threads_created_at');
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('threads');
    }
};
