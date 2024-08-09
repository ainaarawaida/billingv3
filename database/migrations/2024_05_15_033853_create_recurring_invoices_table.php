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
        Schema::dropIfExists('recurring_invoices');
        Schema::create('recurring_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->string('numbering')->nullable();
            $table->text('summary')->nullable();
            $table->date('start_date')->nullable();
            $table->date('stop_date')->nullable();
            $table->string('every')->nullable();
            $table->string('generate_before')->nullable();
            $table->string('status')->nullable();
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
        Schema::dropIfExists('recurring_invoices');
    }
};
