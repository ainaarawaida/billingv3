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
        Schema::dropIfExists('payments');
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->nullable();
            $table->foreignId('invoice_id')->nullable();
            $table->foreignId('recurring_invoice_id')->nullable();
            $table->string('payment_method_id')->nullable();
            $table->date('payment_date')->nullable();
            $table->string('total')->nullable();
            $table->text('notes')->nullable();
            $table->string('reference')->nullable();
            $table->string('status')->nullable();
            $table->json('attachments')->nullable();
           
            
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
