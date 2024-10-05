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
        Schema::create('endpoints', function (Blueprint $table) {
            $table->id();
            $table->integer('base_api_id');
            $table->string('endpoint');
            $table->enum('method',['post','get','put','patch','head','delete']);
            $table->text('description')->nullable();
            $table->enum('status',['enabled','disabled'])->default('enabled');
            $table->json('headers')->nullable();
            $table->json('payload')->nullable();
            $table->json('parameters')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['base_api_id','endpoint','method'],
                'apis_unique_endpoint');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('endpoints');
    }
};
