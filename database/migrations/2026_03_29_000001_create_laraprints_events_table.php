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
        Schema::connection($this->getConnection())->create('laraprints_events', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->nullable()->index();
            $table->string('session_id')->nullable()->index();
            $table->string('visit_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name', 100)->index();
            $table->json('properties')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists('laraprints_events');
    }
};
