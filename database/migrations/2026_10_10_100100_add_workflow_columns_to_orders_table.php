<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // The status set now lives in App\Enums\OrderStatus (it gained "delivered"),
            // so the column is a plain string instead of a database enum.
            $table->string('status', 20)->default('draft')->change();

            $table->string('source', 20)->default('admin')->after('status'); // admin, portal, mcp
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('picking_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('invoice_number', 30)->nullable()->unique();
            $table->timestamp('invoiced_at')->nullable();

            $table->index(['status', 'created_at']);
            $table->index('confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['confirmed_at']);
            $table->dropUnique(['invoice_number']);
            $table->dropColumn([
                'source', 'confirmed_at', 'picking_at', 'shipped_at', 'delivered_at',
                'cancelled_at', 'invoice_number', 'invoiced_at',
            ]);
        });
    }
};
