<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('category', 60)->nullable()->after('sku');
            $table->unsignedInteger('reorder_level')->default(10)->after('stock_quantity');
        });

        // Every change of a product's stock, with the reason. products.stock_quantity
        // is the running balance; the sum of quantity here always equals it.
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->integer('quantity');            // signed: + in, - out
            $table->integer('balance_after');
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['category', 'reorder_level']);
        });
    }
};
