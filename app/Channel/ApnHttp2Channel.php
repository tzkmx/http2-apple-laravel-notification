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
     * @var bool $toProduction should use dev or production endpoint
     */
    protected $toProduction;

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
        $deviceTokens = $notifiable->routeNotificationFor('apn', $notification);

        $apnMessage = $notification->toApn($notifiable);

        if (! $apnMessage instanceof ApnHttp2Message) {
            throw new \LogicException('Notification message not matches expected class');
        }

        if (empty($apnMessage->tokens) && empty($deviceTokens)) {
            return [];
        }


    }

    protected function doSendPush()
    {
        $this->initConnector();



        $message = $this->encodeMessage($detail);

        $pemFile = realpath(resolve('app.dir') . '/../' . $detail->corpKey);
        $passphrase = 'Nuts2002';

        $corpId = $detail->corpId;
        $app_bundle_id = PushesRepository::APPLE_BUNDLE_ID_CORP[$corpId];

        $headers = [
          'apns-topic: ' . $app_bundle_id,
          'User-Agent: Samaya-Push/http2'
        ];


        $hostAPNservice = intval($detail->corpId) === 23
          ? 'https://api.development.push.apple.com/'
          : 'https://api.push.apple.com/';

        $deviceToken = $detail->deviceId;

        $devicePushUrl = $hostAPNservice . '3/device/' . $deviceToken;

        error_log($devicePushUrl, 0);

        $curl_res = curl_init();

        curl_setopt_array($curl_res, [
            CURLOPT_URL => $devicePushUrl,
            CURLOPT_PORT => 443,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $message,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            // TODO: verificar SSL con 'entrust_2048_ca.cer?
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSLCERT => $pemFile,
            CURLOPT_SSLCERTPASSWD => $passphrase,
            CURLOPT_HEADER => 1,
        ]);

        $response = curl_exec($curl_res);
        $err = curl_error($curl_res);
        $status = strval(curl_getinfo($curl_res, CURLINFO_HTTP_CODE));

        error_log(var_export(compact('response', 'err', 'status'), true), 0);

        if ($response === false) {
            throw new \Exception('Failed APN push with error: ' . $err);
        }
        switch ($status) {
            case '200':
                $this->timestampFulfilledPush($detail);
                break;
            case '400':
                $detail->setError([
                  'code' => 'BadRequest',
                  'response' => $response
                ]);
                $this->timestampFulfilledPush($detail);
                break;
            default:
                $detail->setError([
                  'code' => 'error',
                  'response' => $response
                ]);
        }

        if ($err && defined('DEBUG_SAMAYA_DEV') && DEBUG_SAMAYA_DEV) {
            \rawLog($err);
        }

        return $detail;
    }

    protected function configureMessageHeaders(ApnHttp2Message $message)
    {
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

    protected function encodeMessage(IOSPushDetail $detail): string
    {
        $msg = [
            'aps' => $this->buildMessageHeaders($detail),
            'info' => $this->buildMessageBody($detail)
        ];
        // https://gist.github.com/valfer/18e1052bd4b160fed86e6cbb426bb9fc
        return json_encode($msg);
    }

    protected function buildMessageBody(IOSPushDetail $detail): array
    {
        $filteredMsg = html_entity_decode($detail->message, ENT_NOQUOTES, 'UTF-8');
        $filteredTitle = html_entity_decode($detail->title, ENT_NOQUOTES, 'UTF-8');

        return [
          'message' => $filteredMsg,
          'title' => $filteredTitle,
          'user' => 0,
          'id_push' => $detail->pushId,
          'asset_url' => '',
          'asset_type' => 'na'
        ];
    }

    protected function buildMessageHeaders(IOSPushDetail $detail): array
    {
        return [
            'badge' => +1,
            'alert' => html_entity_decode($detail->message, ENT_NOQUOTES, 'UTF-8'),
            'sound' => 'default',
            'content-available' => '1'
        ];
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
