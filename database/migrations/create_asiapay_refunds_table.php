<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asiapay_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asiapay_payment_id')->constrained('asiapay_payments')->cascadeOnDelete();
            $table->string('refund_request_no')->index();
            $table->string('refund_order_id')->nullable()->unique();
            $table->string('status')->default('UNKNOWN')->index();
            $table->decimal('amount', 12, 3)->nullable();
            $table->string('currency', 3)->default('IQD');
            $table->string('reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asiapay_refunds');
    }
};
