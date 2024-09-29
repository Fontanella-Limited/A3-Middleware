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
        Schema::create('base_apis', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint')->unique();
            $table->enum('method',['post','get','put','patch','head','delete']);
            $table->text('description')->nullable();
            $table->enum('status',['enabled','disabled'])->default('enabled');
            $table->json('headers')->nullable();
            $table->json('payload')->nullable();
            $table->json('parameters')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('base_apis');
    }
};
