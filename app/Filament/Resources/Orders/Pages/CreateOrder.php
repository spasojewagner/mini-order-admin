<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\OrderWorkflow;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

/** Creates a draft; lines are added on the next screen, then the order is confirmed. */
class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        $order = new Order([
            'customer_id' => $data['customer_id'],
            'note' => $data['note'] ?? null,
            'source' => 'admin',
            'total_amount' => 0,
        ]);
        $order->status = OrderStatus::Draft;
        $order->save();

        app(OrderWorkflow::class)->log($order, 'created', null, OrderStatus::Draft, 'admin', auth()->user());

        return $order;
    }

    protected function getRedirectUrl(): string
    {
        return OrderResource::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Draft created. Add the lines, then confirm the order.';
    }
}
