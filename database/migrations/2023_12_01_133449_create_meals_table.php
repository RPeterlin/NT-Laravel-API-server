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
        Schema::create('meals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->string('name');
            $table->string('unit');
            $table->string('category')->nullable();
            $table->integer('calories');
            $table->integer('tfat')->nullable();
            $table->integer('sfat')->nullable();
            $table->integer('carbs')->nullable();
            $table->integer('sugar')->nullable();
            $table->integer('protein')->nullable();
            // image path TODO
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meals');
    }
};
