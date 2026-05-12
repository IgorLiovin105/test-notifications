<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            $table->integer('user_id');
            $table->text('message');

            $table->enum('status', ['pending', 'sent', 'failed']);
            $table->enum('channel', ['email', 'telegram']);

            $table->unsignedTinyInteger('retry_count')->default(0);

            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index('channel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
