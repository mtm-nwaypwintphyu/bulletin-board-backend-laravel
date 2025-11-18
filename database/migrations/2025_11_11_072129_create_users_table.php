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
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');

            $table->string('name')->unique();
            $table->string('email')->unique();
            $table->text('password');

            $table->string('profile', 255)->nullable();

            $table->string('type', 1)->default('1'); // 0 = Admin, 1 = User

            $table->string('phone', 20)->nullable();
            $table->string('address', 255)->nullable();
            $table->date('dob')->nullable();

            $table->unsignedInteger('create_user_id');
            $table->unsignedInteger('updated_user_id');
            $table->unsignedInteger('deleted_user_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

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
        Schema::dropIfExists('users');
    }
};
