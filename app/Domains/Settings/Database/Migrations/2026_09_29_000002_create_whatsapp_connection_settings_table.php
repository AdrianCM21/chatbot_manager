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
        Schema::create('whatsapp_connection_settings', function (Blueprint $table) {
            $table->id();
            $table->text('access_token')->nullable();
            $table->string('phone_number_id')->nullable();
            $table->string('business_account_id')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('last_verification_error')->nullable();
            $table->timestamps();
        });

        // Fila única (patrón singleton): toda la app lee/escribe siempre el id 1.
        DB::table('whatsapp_connection_settings')->insert(['created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_connection_settings');
    }
};
