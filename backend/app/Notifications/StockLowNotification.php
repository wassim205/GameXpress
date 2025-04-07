<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;


class StockLowNotification extends Notification
{
    use Queueable;

    public $products;
    /**
     * Create a new notification instance.
     */
    public function __construct(Collection $products)
    {
        $this->products = $products;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $productNames = $this->products->take(3)->pluck('name')->toArray();
        $moreProducts = $this->products->count() > 3 ? '...' : '';
        $productList = implode(', ', $productNames) . " $moreProducts";

        return (new MailMessage)
            ->subject('Alerte Stock Critique')
            ->greeting('Bonjour Admin,')
            ->line("Les produits suivants sont bientôt en rupture de stock : **{$productList}**.")
            ->action('Voir les produits', url('/admin/products'))
            ->line('Veuillez recharger le stock dès que possible.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
