<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;


class StockLowNotification extends Notification
{
    use Queueable;

    public $product;
    /**
     * Create a new notification instance.
     */
    public function __construct(Product $product)
    {
        $this->product = $product;
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
       return (new MailMessage)
    ->subject('Alerte Stock Critique')
    ->greeting('Bonjour Admin,')
    ->line("Le produit **{$this->product->name}** est bientôt en rupture de stock !")
    ->line("Stock restant : **{$this->product->stock} unités**.")
    ->action('Voir le produit', url('/admin/products/' . $this->product->id))
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
