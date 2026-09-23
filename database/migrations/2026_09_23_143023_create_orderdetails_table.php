<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orderdetails', function (Blueprint $table) {
            $table->id('orderdetailID');
            $table->unsignedBigInteger('orderID');
            $table->unsignedBigInteger('menuID');
            $table->integer('quantity');
            $table->decimal('subtotal');
            $table->foreign('orderID')->references('orderID')->on('orders')->onDelete('cascade');
            $table->foreign('menuID')->references('menuID')->on('menuitems')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orderdetails');
    }
};
