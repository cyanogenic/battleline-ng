<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('battle_line_games', function (Blueprint $table) {
            $table->id();
            $table->string('player_one_name');
            $table->string('player_two_name');
            $table->string('status');
            $table->string('winner_name')->nullable();
            $table->json('state');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('battle_line_games');
    }
};
