<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demand_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demand_id')->constrained('demands')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('comment');
            $table->timestamps();
            $table->index(['demand_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demand_comments');
    }
};

