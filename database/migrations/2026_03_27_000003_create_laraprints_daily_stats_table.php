<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function getConnection(): ?string
    {
        return config('laraprints.database.connection');
    }

    public function up(): void
    {
        Schema::connection($this->getConnection())->create('laraprints_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->unsignedBigInteger('page_views')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->unsignedBigInteger('unique_sessions')->default(0);
            $table->unsignedBigInteger('desktop')->default(0);
            $table->unsignedBigInteger('mobile')->default(0);
            $table->unsignedBigInteger('unknown')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists('laraprints_daily_stats');
    }
};
