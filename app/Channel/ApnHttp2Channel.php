<?php

namespace App\Channel;

use Carbon\Carbon;
use Illuminate\Notifications\Notification;
/**
 * Sender of Apple Push Http/2 Messages.
 * Based on the code of http://coding.tabasoft.it/ios/sending-push-notification-with-http2-and-php/
 */
class ApnHttp2Channel
{
    /**
     * @var array $msgHeaders
     */
    protected $msgHeaders;

    /**
     * @var string $encodedMessageBody
     */
    protected $encodedMessageBody;

    /**
     * @var string $apnHost where to connect to send requests
     */
    protected $apnHost;

    /**
     * @var array $responses for send notifications call
     */
    protected $responses = [];

    /**
     * @var string $certsPath where to find the certificate(s) for push
     */
    protected $certsPath;

    /**
     * @var string $certFile specific filename for this process
     */
    protected $certFile;

    /**
     * @var string $certPassword password to decrypt the PEM push certificate
     */
    protected $certPassword;

    /**
     * @var resource $curlResource
     */
    protected static $curlResource;

    public function __construct()
    {
        if (!defined('CURL_HTTP_VERSION_2_0')) {
            define('CURL_HTTP_VERSION_2_0', 3);
        }
    }

    /**
     * @param $notifiable
     * @param Notification $notification
     *
     * @return array
     * @throws \Exception
     */
    public function send($notifiable, Notification $notification)
    {
        $this->responses = [];
        $deviceTokens = $notifiable->routeNotificationFor('apn', $notification);

        $apnMessage = $notification->toApn($notifiable);

        if (! $apnMessage instanceof ApnHttp2Message) {
            throw new \LogicException('Notification message not matches expected class');
        }

        if (empty($apnMessage->tokens) && empty($deviceTokens)) {
            return $this->responses;
        }

        if (is_string($deviceTokens)) {
            $this->doSendPush($deviceTokens, $apnMessage);
        }
        if (is_array($deviceTokens)) {
            foreach ($deviceTokens as $token) {
                $this->doSendPush($token, $apnMessage);
            }
        }
        $msgToken = $apnMessage->tokens;
        if (is_string($msgToken) && !empty($msgToken)) {
            $this->doSendPush($msgToken, $apnMessage);
        }
        if (is_array($msgToken)) {
            foreach ($msgToken as $token) {
                $this->doSendPush($token, $apnMessage);
            }
        }
        return $this->responses;
    }

    protected function doSendPush(string $token, ApnHttp2Message $message)
    {
        $this->initConnector();
        $this->configureApnHost();
        $this->configureMessageHeaders($message);
        $this->encodeMessage($message);
        $this->configurePushCertificate($message);
        $this->configureCertificatePassword($message);

        $devicePushUrl = $this->apnHost . '/3/device/' . $token;

        curl_setopt_array(self::$curlResource, [
            CURLOPT_URL => $devicePushUrl,
            CURLOPT_PORT => 443,
            CURLOPT_HTTPHEADER => $this->msgHeaders,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $this->encodedMessageBody,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            // TODO: verificar SSL con 'entrust_2048_ca.cer?
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSLCERT => $this->certFile,
            CURLOPT_SSLCERTPASSWD => $this->certPassword,
            CURLOPT_HEADER => 1,
        ]);

        $response = curl_exec(self::$curlResource);
        $err = curl_error(self::$curlResource);
        $status = strval(curl_getinfo(self::$curlResource, CURLINFO_HTTP_CODE));

        $this->responses[] = compact('response', 'status', 'err');
    }

    protected function configureMessageHeaders(ApnHttp2Message $message)
    {
        if (! empty($this->msgHeaders)) return;

        $headers = $message->getHeaders();
        $matchable = implode(';', $headers);

        if (!preg_match('/User-Agent/', $matchable)) {
            $headers[] = 'User-Agent: ' . config('apn_push.user_agent');
        }

        if (!preg_match('/apns-topic/', $matchable)) {
            $headers[] = 'apns-topic: ' . config('apn_push.apns_topic');
        }

        if (!preg_match('/apns-priority/', $matchable)) {
            $headers[] = 'apns-priority: 10';
        }

        if (!preg_match('/apns-expiration/', $matchable)) {
            $expiration = Carbon::now()
              ->addSeconds(24 * 60 * 60)
              ->getTimestamp();
            $headers[] = 'apns-expiration: ' . $expiration;
        }
        $this->msgHeaders = $headers;
    }

    protected function encodeMessage(ApnHttp2Message $message)
    {
        if (empty($this->encodedMessageBody)) {
            $this->encodedMessageBody = json_encode($message);
        }
    }

    protected function configureApnHost()
    {
        if (empty($this->apnHost)) {
            $this->apnHost = config('apn_push.apns_production')
              ? 'https://api.push.apple.com'
              : 'https://api.development.push.apple.com';
        }
    }

    protected function configurePushCertificate(ApnHttp2Message $message)
    {
        if (empty($this->certsPath)) {
            $this->certsPath = empty($message->certificatePath)
              ? config('apn_push.certificates_dir')
              : $message->certificatePath;
        }
        if (empty($this->certFile)) {
            $certFile = empty($message->certificateFile)
              ? config('apn_push.certificate_file')
              : $message->certificateFile;
            $this->certFile = realpath(
              $this->certsPath . '/' .
              $certFile
            );
        }
    }

    protected function configureCertificatePassword(ApnHttp2Message $message)
    {
        if (empty($this->certPassword)) {
            $this->certPassword = empty($message->certPassword)
              ? config('apn_push.cert_decrypt_password')
              : $message->certPassword;
        }
    }

    protected function initConnector()
    {
        if (is_null(self::$curlResource)) {
            self::$curlResource = curl_init();
        }
    }

    protected function closeConnection()
    {
        curl_close(self::$curlResource);
        self::$curlResource = null;
    }

    protected function __destruct()
    {
        $this->closeConnection();
    }
}
