<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incoming_messages', function (Blueprint $table) {
            $table->id();
            $table->string('from_number');
            $table->string('type');
            $table->text('body')->nullable();
            $table->unsignedInteger('matched_products_count')->default(0);
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incoming_messages');
    }
};
