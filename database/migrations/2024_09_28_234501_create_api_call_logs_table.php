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
        Schema::create('api_call_logs', function (Blueprint $table) {
            $table->id();
            // $table->foreign('user_id')->references('id')
            // ->on('users')->onupdate('cascade')->onDelete('cascade');
            $table->string('base_api_id')->references('id')
            ->on('base_apis')->onupdate('cascade')->onDelete('cascade');
            $table->json('response')->nullable();
            $table->integer('response_time');
            $table->enum('status',['success','failed']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_call_logs');
    }
};
