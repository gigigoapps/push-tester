<?php

namespace PushTester\Push\Gateway;

class GcmGateway implements GatewayInterface
{
    const GATEWAY_URL  = "https://android.googleapis.com/gcm/send";

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
        return 'gcm';
    }

    /**
     * @param array $data
     * @return string
     * @throws \Exception
     */
    public function send(array $data)
    {
        $result = '-';

        if (!$this->auth || trim($this->auth) == '') {
            throw new \Exception("No auth provided for gateway");
        }

        // set request headers
        $headers = [
            'Authorization: key=' . $this->auth,
            'Content-Type: application/json'
        ];

        // message payload
        $message = json_encode([
            "registration_ids" => $data['devices'],
            "data" => [
                "message" => $data['message'],
                "typeAlert" => $data['typeAlert'],
                "title" => "Push Tester",
                "badge" => $data['badge'],
                "sound" => $data['sound']
            ]
        ]);

        // build request
        $ch = curl_init(self::GATEWAY_URL);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        curl_close($ch);

        if (trim($body) !== '') {
            $message = json_decode($body);

            if (preg_match("/unauthorized/i", $body)) {
                throw new \Exception("Unauthorized [ERROR 401]");

            } else if ($message === null) {
                throw new \Exception("No response [ERROR A]");

            } else if ($message->success == 0) {
                throw new \Exception("No send push notifications [ERROR B]");

            } else if ($message->failure > 0) {
                throw new \Exception("Push notifications [ERROR]");

            } else if ($message->success > 0) {
                $result = "Push notifications [OK]";
            }
        } else {
            throw new \Exception("No send push notifications [ERROR C]");
        }
        
        return $result;
    }
}
