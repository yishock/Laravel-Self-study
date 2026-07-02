<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_user_list_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_user_id');
            $table->string('page_key', 120);
            $table->json('preferences');
            $table->timestamps();

            $table->unique(['admin_user_id', 'page_key']);
            $table->foreign('admin_user_id')
                ->references('id')
                ->on('admin_users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_user_list_preferences');
    }
};
