<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(true);
            $table->timestamp('paused_at')->nullable();
            $table->foreignId('paused_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Fila única (patrón singleton): toda la app lee/escribe siempre el id 1.
        DB::table('bot_settings')->insert(['enabled' => true, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_settings');
    }
};
