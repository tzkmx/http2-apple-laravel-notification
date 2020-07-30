<?php

namespace App\Channel;

use Carbon\Carbon;
use Illuminate\Support\Arr;

class ApnHttp2Message implements \JsonSerializable
{
    /** @var string */
    public $title = '';

    /** @var string */
    public $subtitle = '';

    /** @var string */
    public $body = '';

    /** @var int */
    public $expiration = 0;

    /** @var string|array  */
    public $sound = 'default';

    /** @var int */
    public $badge = 1;

    /** @var string */
    public $topic = '';

    /** @var string */
    public $certificatePath = '';

    /** @var string */
    public $certificateFile = '';

    /** @var string */
    public $certPassword = '';

    /** @var int */
    public $priority = 10;

    /** @var string */
    public $uuid = '';

    /** @var array */
    public $customData = [];

    /** @var string */
    public $userAgent = '';

    /** @var string|string[] */
    public $tokens = '';

    /**
     * ApnHttp2Message constructor.
     * @param string $title
     * @param string $subtitle
     * @param string $body
     * @param array $customData
     */
    public function __construct(string $title, string $subtitle, string $body, array $customData = [])
    {
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->body = $body;
        $this->customData = $customData;
    }

    public static function create(string $title, string $subtitle, string $body, array $customData = [])
    {
        return new self($title, $subtitle, $body, $customData);
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4
     */
    public function jsonSerialize()
    {
        $alertBase = $this->title !== '' && $this->subtitle !== ''
          ? html_entity_decode($this->body)
          : [
            'title' => html_entity_decode($this->title),
            'subtitle' => html_entity_decode($this->subtitle),
            'body' => html_entity_decode($this->body),
          ];
        $aps = [
          'aps' => [
            'alert' => $alertBase,
            'badge' => $this->badge,
            'sound' => $this->sound,
            'content-available' => '1',
          ],
        ];
        return $this->customData + $aps;
    }

    public function getHeaders(): array
    {
        $headers = [];

        if ($this->userAgent !== '') {
            $headers[] = 'User-Agent: ' . $this->userAgent;
        }

        if ($this->topic !== '') {
            $headers[] = 'apns-topic: ' . $this->topic;
        }

        if ($this->priority !== 0) {
            $headers[] = 'apns-priority: ' . $this->priority;
        }

        if ($this->uuid) {
            $headers[] = 'apns-id: ' . $this->uuid;
        }

        if ($this->expiration !== 0) {
            $expiration = Carbon::now()
              ->addSeconds($this->expiration)
              ->getTimestamp();
            $headers[] = 'apns-expiration: ' . $expiration;
        }

        return $headers;
    }

    /**
     * @param string $title
     * @return ApnHttp2Message
     */
    public function setTitle(string $title): ApnHttp2Message
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @param string $subtitle
     * @return ApnHttp2Message
     */
    public function setSubtitle(string $subtitle): ApnHttp2Message
    {
        $this->subtitle = $subtitle;
        return $this;
    }

    /**
     * @param string $body
     * @return ApnHttp2Message
     */
    public function setBody(string $body): ApnHttp2Message
    {
        $this->body = $body;
        return $this;
    }

    /**
     * @param int $expiration
     * @return ApnHttp2Message
     */
    public function setExpiration(int $expiration): ApnHttp2Message
    {
        $this->expiration = $expiration;
        return $this;
    }

    /**
     * @param array|string $sound
     * @return ApnHttp2Message
     */
    public function setSound($sound)
    {
        $this->sound = $sound;
        return $this;
    }

    /**
     * @param int $badge
     * @return ApnHttp2Message
     */
    public function setBadge(int $badge): ApnHttp2Message
    {
        $this->badge = $badge;
        return $this;
    }

    /**
     * @param string $topic
     * @return ApnHttp2Message
     */
    public function setTopic(string $topic): ApnHttp2Message
    {
        $this->topic = $topic;
        return $this;
    }

    /**
     * @param string $certificatePath
     * @return ApnHttp2Message
     */
    public function setCertificatePath(string $certificatePath): ApnHttp2Message
    {
        $this->certificatePath = $certificatePath;
        return $this;
    }

    /**
     * @param string $certificateFile
     * @return ApnHttp2Message
     */
    public function setCertificateFile(string $certificateFile): ApnHttp2Message
    {
        $this->certificateFile = $certificateFile;
        return $this;
    }

    /**
     * @param string $certPassword
     * @return ApnHttp2Message
     */
    public function setCertPassword(string $certPassword): ApnHttp2Message
    {
        $this->certPassword = $certPassword;
        return $this;
    }

    /**
     * @param int $priority
     * @return ApnHttp2Message
     */
    public function setPriority(int $priority): ApnHttp2Message
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * @param string $uuid
     * @return ApnHttp2Message
     */
    public function setUuid(string $uuid): ApnHttp2Message
    {
        $this->uuid = $uuid;
        return $this;
    }

    /**
     * @param string $key can be specified as key accesor as in Arr helper
     * @param $value
     * @return $this
     */
    public function addCustomData(string $key, $value): ApnHttp2Message
    {
        Arr::set($this->customData, $key, $value);
        return $this;
    }

    /**
     * @param string $userAgent
     * @return ApnHttp2Message
     */
    public function setUserAgent(string $userAgent): ApnHttp2Message
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    /**
     * @param string|string[] $tokens
     * @return ApnHttp2Message
     */
    public function setTokens($tokens)
    {
        $this->tokens = $tokens;
        return $this;
    }
}
