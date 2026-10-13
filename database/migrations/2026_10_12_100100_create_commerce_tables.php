<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->unsignedInteger('fee_minor');
            $table->unsignedInteger('free_over_minor')->nullable();
            $table->string('estimate', 80)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('description', 160)->nullable();
            $table->string('type', 10);
            $table->unsignedInteger('value');
            $table->unsignedInteger('min_subtotal_minor')->nullable();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('uses_count')->default(0);
            $table->boolean('once_per_customer')->default(false);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->unique();
            $table->string('name', 120);
            $table->string('email')->nullable();
            $table->string('city', 80)->nullable();
            $table->timestamp('first_order_at')->nullable();
            $table->timestamp('last_order_at')->nullable();
            $table->timestamp('anonymized_at')->nullable();
            $table->timestamps();

            $table->index('name');
        });

        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->char('token', 64)->unique();
            $table->string('coupon_code', 40)->nullable();
            $table->timestamps();

            $table->index('updated_at');
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('quantity');
            $table->timestamps();

            $table->unique(['cart_id', 'product_variant_id']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->char('idempotency_key', 36)->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name', 120);
            $table->string('customer_phone', 20);
            $table->string('customer_email')->nullable();
            $table->string('city', 80);
            $table->string('address', 255);
            $table->string('delivery_notes', 500)->nullable();
            $table->foreignId('shipping_zone_id')->nullable()->constrained()->nullOnDelete();
            $table->string('shipping_zone_name', 80);
            $table->char('currency', 3);
            $table->unsignedInteger('subtotal_minor');
            $table->unsignedInteger('discount_minor')->default(0);
            $table->unsignedInteger('shipping_minor')->default(0);
            $table->unsignedInteger('total_minor');
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('coupon_code', 40)->nullable();
            $table->string('payment_method', 10)->default('cod');
            $table->string('payment_status', 16)->default('pending');
            $table->timestamp('payment_collected_at')->nullable();
            $table->unsignedInteger('payment_collected_minor')->nullable();
            $table->foreignId('payment_recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 24);
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('courier_name', 80)->nullable();
            $table->string('tracking_number', 80)->nullable();
            $table->json('risk_flags')->nullable();
            $table->timestamp('privacy_consent_at');
            $table->char('ip_hash', 64)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('created_at');
            $table->index('customer_phone');
            $table->index(['payment_status', 'status']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->string('product_name', 160);
            $table->string('variant_name', 80)->nullable();
            $table->string('sku', 64)->nullable();
            $table->unsignedInteger('unit_price_minor');
            $table->unsignedInteger('regular_unit_price_minor');
            $table->unsignedSmallInteger('quantity');
            $table->unsignedInteger('line_total_minor');
            $table->timestamps();
        });

        Schema::create('order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('type', 12);
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(['order_id', 'created_at']);
        });

        Schema::create('coupon_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('amount_minor');
            $table->timestamp('released_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['coupon_id', 'customer_id']);
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->integer('change');
            $table->unsignedInteger('stock_after');
            $table->string('reason', 20);
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note', 255)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['product_variant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('coupon_redemptions');
        Schema::dropIfExists('order_events');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('shipping_zones');
    }
};
