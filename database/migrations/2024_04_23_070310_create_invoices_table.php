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
        Schema::dropIfExists('invoices');
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->string('numbering')->nullable();
            $table->date('invoice_date')->nullable();
            $table->date('pay_before')->nullable();
            $table->string('invoice_status')->nullable();
            $table->text('summary')->nullable();
            $table->decimal('sub_total', 10, 2)->nullable();
            $table->decimal('taxes', 10, 2)->nullable();
            $table->decimal('percentage_tax', 5, 2)->nullable();
            $table->decimal('delivery', 10, 2)->nullable();
            $table->decimal('final_amount', 10, 2)->nullable();
            $table->decimal('balance', 10, 2)->nullable();
            $table->foreignId('recurring_invoice_id')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->text('footer')->nullable();
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
        Schema::dropIfExists('invoices');
    }
};
