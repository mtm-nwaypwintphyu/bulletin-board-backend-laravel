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
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');

            $table->string('title', 255)->unique();
            $table->text('description');
            $table->integer('status')->default(1);

            $table->unsignedInteger('create_user_id');
            $table->unsignedInteger('updated_user_id');
            $table->unsignedInteger('deleted_user_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('create_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('updated_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('deleted_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
