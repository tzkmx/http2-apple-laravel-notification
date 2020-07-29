<?php

namespace App\Channel;

class IOSPushService_HTTP2
{
    /**
     * @var string $userAgent user agent for request
     */
    protected $userAgent;

    /**
     * @var string $apnsTopic same as App Bundle Id
     */
    protected $apnsTopic;

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

    public function __construct()
    {
        if (!defined('CURL_HTTP_VERSION_2_0')) {
            define('CURL_HTTP_VERSION_2_0', 3);
        }
    }

    public function sendPush(IOSPushDetailInterface $detail)
    {
        if($maybeDateSent = $detail->getStatusSent(true)) {
            throw new \LogicException('Notificación ya enviada: ' . $maybeDateSent->format(DATE_ISO8601));
        }

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

    protected function timestampFulfilledPush(AbstractPushDetail $detail): AbstractPushDetail
    {
        $detail->setDatePushSent(new \DateTimeImmutable());
        return $detail;
    }
}
