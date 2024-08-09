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
        Schema::dropIfExists('team_settings');
        Schema::create('team_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('quotation_prefix_code')->nullable();
            $table->string('quotation_current_no')->nullable();
            $table->string('quotation_template')->nullable();

            $table->string('invoice_prefix_code')->nullable();
            $table->string('invoice_current_no')->nullable();
            $table->string('invoice_template')->nullable();

            $table->string('recurring_invoice_prefix_code')->nullable();
            $table->string('recurring_invoice_current_no')->nullable();


            $table->json('payment_gateway')->nullable();
            $table->timestamps();
        });
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_settings');
    }
};
