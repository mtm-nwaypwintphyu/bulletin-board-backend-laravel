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
        Schema::create('post_import_history', function (Blueprint $table) {
            $table->id();
            $table->string('import_file');
            $table->timestamp('import_timestamp')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->integer('records_imported');
            $table->string('status',50);
            $table->text('error_message')->nullable();
            $table->foreignId('user_id')->refrences('id')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_import_history');
    }
};
