<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_logs', function (Blueprint $table) {
            $table->id('accesslogID');
            $table->unsignedBigInteger('accountID')->nullable();
            $table->string('username')->nullable();
            $table->string('email')->nullable();
            $table->string('role')->nullable();
            $table->string('event'); // e.g., Login Success, Login Failed, 2FA Verified, Logout
            $table->string('status')->default('success'); // success, failed, warning
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('details')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('error_logs', function (Blueprint $table) {
            $table->id('errorlogID');
            $table->unsignedBigInteger('accountID')->nullable();
            $table->string('username')->nullable();
            $table->string('level')->default('error'); // error, critical, warning
            $table->string('exception')->nullable();
            $table->text('message');
            $table->string('file')->nullable();
            $table->integer('line')->nullable();
            $table->text('url')->nullable();
            $table->string('method', 10)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->longText('trace')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_logs');
        Schema::dropIfExists('access_logs');
    }
};