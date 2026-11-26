<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();$table->string('system_name')->default('Cafe Ordering System');
            $table->string('system_logo')->nullable();$table->timestamps();
        });

        DB::table('system_settings')->insert([
            'system_name' => 'Cafe Ordering System',
            'system_logo' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('accounts', function (Blueprint $table) {$table->string('profile_picture')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {$table->dropColumn('profile_picture');
        });

        Schema::dropIfExists('system_settings');
    }
};