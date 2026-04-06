<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('battle_line_games', function (Blueprint $table) {
            $table->foreignId('player_one_user_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('player_two_user_id')
                ->nullable()
                ->after('player_one_name')
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('winner_user_id')
                ->nullable()
                ->after('status')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('battle_line_games', function (Blueprint $table) {
            $table->dropConstrainedForeignId('winner_user_id');
            $table->dropConstrainedForeignId('player_two_user_id');
            $table->dropConstrainedForeignId('player_one_user_id');
        });
    }
};
