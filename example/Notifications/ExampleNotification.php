<?php

namespace App\Notifications;

use Apantle\ApnHttp2Notification\ApnHttp2Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ExampleNotification extends Notification
{
    use Queueable;

    /**
     * @var string título de la notificación, provisto por cliente
     */
    protected $title;

    /**
     * @var string mensaje del push
     */
    protected $message;

    /**
     * BasicNotification constructor.
     * @param string $title
     * @param string $message
     */
    public function __construct(string $title, string $message)
    {
        $this->title = $title;
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['apn'];
    }

    public function toApn($notifiable): ApnHttp2Message
    {
        return ApnHttp2Message::create(
          $this->title,
          '',
          $this->message,
          [
            'message' => $this->message,
            'title' => $this->title,
            'user' => 0,
            'id_push' => date('U'),
            'asset_url' => '',
            'asset_type' => 'na',
          ]
        )
          ->setTopic(config('apn_push.apns_topic'))
          ->setCertificateFile(config('apn_push.certificate_file'))
          ->setCertPassword(config('apn_push.cert_decrypt_password'))
        ;
    }
}
