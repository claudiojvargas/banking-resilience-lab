<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamps();
        });

        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('identifier')->unique();
            $table->bigInteger('balance_cents');
            $table->timestamps();
        });

        Schema::create('transfers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('source_account_id')->constrained('accounts');
            $table->foreignId('destination_account_id')->constrained('accounts');
            $table->bigInteger('amount_cents');
            $table->string('status');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfers');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('users');
    }
};
