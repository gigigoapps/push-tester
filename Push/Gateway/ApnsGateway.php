<?php

namespace PushTester\Push\Gateway;

class ApnsGateway implements GatewayInterface
{
    const GATEWAY_URL = "tls://gateway.push.apple.com";
    const GATEWAY_PORT = 2195;

    protected $auth = null;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->auth = $config['auth'];
    }

    /**
     * @return string
     */
    public static function platform()
    {
        return 'apns';
    }

    /**
     * @param array $data
     * @return string
     * @throws \Exception
     */
    public function send(array $data)
    {
        if (!$this->auth || trim($this->auth) == '') {
            throw new \Exception("No auth provided for gateway");
        }

        // message payload
        $payload = json_encode([
            "aps" => [
                "alert" => $data['message'],
                "typeAlert" => $data['typeAlert'],
                "badge" => $data['badge'],
                "sound" => isset($data['sound']) ? $data['sound'] : 'default'
            ]
        ]);


        $stream_context = stream_context_create();
        stream_context_set_option($stream_context, 'ssl', 'local_cert', $this->auth);
        $apnsSocket = stream_socket_client(self::GATEWAY_URL.':'.self::GATEWAY_PORT, $error, $errorString, 5, STREAM_CLIENT_ASYNC_CONNECT, $stream_context);

        foreach ($data['devices'] as $key => $result) {
            $apnsMessage = chr(0) . chr(0) . chr(32) . pack('H*', str_replace(' ', '', $data['devices'][$key])) . chr(0) . chr(strlen($payload)) . $payload;

            if (fwrite($apnsSocket, $apnsMessage) === false) {
                throw new \Exception("Not writed to the socket. " . $error . " " . $errorString);
            }
        }

        return "IOS Notificaciones Push: [OK]";
    }
}
