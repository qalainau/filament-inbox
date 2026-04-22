<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('messages')->nullOnDelete();
            $table->foreignId('thread_id')->nullable()->constrained('messages')->nullOnDelete();
            $table->string('subject');
            $table->text('body');
            $table->timestamp('sender_deleted_at')->nullable();
            $table->timestamps();

            $table->index('thread_id');
            $table->index('sender_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
