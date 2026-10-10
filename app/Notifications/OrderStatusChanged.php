<?php

namespace App\Notifications;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Support\Settings;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Email to the customer when their order is confirmed, shipped, delivered or cancelled. */
class OrderStatusChanged extends Notification
{
    use Queueable;

    public function __construct(public Order $order, public OrderStatus $status) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order->loadMissing('items');
        $company = Settings::get('company.name');
        $number = $order->number();

        $mail = (new MailMessage)
            ->subject(match ($this->status) {
                OrderStatus::Confirmed => "Order {$number} confirmed",
                OrderStatus::Shipped => "Order {$number} is on its way",
                OrderStatus::Delivered => "Order {$number} delivered",
                OrderStatus::Cancelled => "Order {$number} cancelled",
                default => "Order {$number}: {$this->status->label()}",
            })
            ->greeting('Hello '.($notifiable->name ?? '').',')
            ->line(match ($this->status) {
                OrderStatus::Confirmed => 'We have confirmed your order and reserved the goods. We will let you know when it ships.',
                OrderStatus::Shipped => "Your order has left our warehouse. Invoice {$order->invoice_number} is ready in the portal.",
                OrderStatus::Delivered => 'Your order has been delivered. Thank you for your business.',
                OrderStatus::Cancelled => 'Your order has been cancelled. If this is unexpected, reply to this email.',
                default => "Your order is now {$this->status->label()}.",
            });

        foreach ($order->items as $item) {
            $mail->line("{$item->quantity} × {$item->product_name} — ".number_format((float) $item->subtotal, 2, ',', '.').' RSD');
        }

        $mail->line('**Total (excl. VAT): '.number_format((float) $order->total_amount, 2, ',', '.').' RSD**')
            ->action('View order', route('my-orders.show', $order))
            ->salutation("{$company}");

        return $mail;
    }
}
