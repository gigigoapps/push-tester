<?php

namespace PushTester\Push;

use PushTester\Push\Gateway\ApnsGateway;
use PushTester\Push\Gateway\GcmGateway;

/**
 * Class to send push to selected service
 */
class NotificationPush
{
    /** @var string Gateway platform key */
    private $platform;

    /** @var mixed Auth method for gateway */
    private $auth;

    /** @var array Device token list */
    private $devices;

    /** @var string Message for push */
    private $message;

    /** @var int Badge for push */
    private $badge;

    /** @var string Type alert for push */
    private $typeAlert;

    /** @var string Sound for push */
    private $sound;

    public function __construct($platform, $auth)
    {
        $this->platform = strtolower($platform);
        $this->auth = $auth;

        $this->message = 'Test message';
        $this->badge = 1;
        $this->devices = [];
    }

    /**
     * Add a device notification token
     *
     * @param string $notificationToken
     */
    public function addNotificationToken($notificationToken)
    {
        $this->devices[] = $notificationToken;
    }

    /**
     * @param array $data
     *  - message: string optional
     *  - typeAlert: string optional
     *  - sound: string optional
     *  - badge: int optional
     * @return string
     * @throws \Exception
     */
    public function send(array $data)
    {
        $data = [
            'platform'  => $this->platform,
            'devices'   => $this->devices,
            'message'   => isset($data['message']) ? $data['message'] : $this->message,
            'typeAlert' => isset($data['typeAlert']) ? $data['typeAlert'] : $this->typeAlert,
            'sound'     => isset($data['sound']) ? $data['sound'] : $this->sound,
            'badge'     => isset($data['badge']) ? $data['badge'] : $this->badge,
        ];

        $gateway = null;
        switch ($this->platform) {
            case GcmGateway::platform():
                $gateway = new GcmGateway(['auth' => $this->auth]);
                break;

            case ApnsGateway::platform():
                $gateway = new ApnsGateway(['auth' => $this->auth]);
                break;

            default:
                throw new \Exception("Gateway provided \"{$this->platform}\" not valid");
        }

        return $gateway->send($data);
    }
}
