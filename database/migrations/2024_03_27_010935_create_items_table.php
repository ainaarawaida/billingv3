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
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('items');
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->nullable();
            $table->foreignId('invoice_id')->nullable();
            $table->foreignId('recurring_invoice_id')->nullable();
            $table->foreignId('product_id')->nullable();
            
            $table->text('title')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('tax')->nullable();
            $table->integer('quantity')->default(0);
            $table->string('unit')->nullable();
            $table->decimal('total', 10, 2)->nullable();
   
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
