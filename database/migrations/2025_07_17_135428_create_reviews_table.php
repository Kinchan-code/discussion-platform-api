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
        Schema::create('reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('protocol_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('rating')->unsigned(); // 1 to 5 usually
            $table->text('feedback')->nullable();
            $table->json('categories')->nullable();
            $table->string('author');
            $table->timestamps();

            $table->index('protocol_id', 'idx_reviews_protocol');
            $table->index('author', 'idx_reviews_author');
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
