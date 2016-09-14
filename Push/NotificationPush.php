<?php
namespace PushTester\Push;

use PushTester\Push\Gateway\Gcm;
use PushTester\Push\Gateway\Ios;

/**
 * Clase encargada de enviar notificaciones push para ios y android
 * @author gigigo
 */
class NotificationPush {

    private $devices;
    private $message;
    private $sound;
    private $gateway;
    private $urlGatewayAndroid;
    private $urlGatewayIos;
    private $gcmtoken;
    private $iosPemFile;
    private $badge;
    private $typeAlert;

    /**
     * Contructor
     * @param StdClass $config
     */
    public function __construct($config) {
        $this->badge = 1;
        $this->devices = array();
        $this->urlGatewayAndroid = $config['android'];
        $this->urlGatewayIos = $config['ios'];
        $this->gcmtoken = $config['gcmToken'];
        $this->iosPemFile = $config['iosPemFile'];
    }

    /**
     * NotificacionToken del dispositivo a enviar
     * @param unknown $notificationToken
     */
    public function addNotificationToken($notificationToken) {
        $this->devices[] = $notificationToken;
    }

    /**
     * Texto message de la notificacion push
     * @param String $message
     */
    public function setMessage($message) {
        $this->message = $message;
    }

    /**
     * Sonido para la notificacion push
     * @param String $sound
     */
    public function setSound($sound) {
        $this->sound = $sound;
    }

    /**
     * Badge para la notificacion push
     * @param String $sound
     */
    public function setBadge($badge) {
        $this->badge = $badge;
    }

    /**
     * Puerta de enlace para el envio de la notificacion push
     *   Valores posibles:
     *     - android
     *     - ios
     * @param String $gateway
     */
    public function setGateway($gateway) {
        $this->gateway = strtolower($gateway);
    }
        
    public function setTypeAlert($typeAlert) {
        $this->typeAlert = $typeAlert;
    }

    /**
     * Envia la notificacion push
     */
    public function send()
    {
        // Preparar el objecto data para entregarselo al gateway
        $data = new \stdClass();
        $data->message = $this->message;
        $data->typeAlert = $this->typeAlert;
        $data->technology = $this->gateway;
        $data->sound = $this->sound;
        $data->devices = $this->devices;
        $data->badge = $this->badge;

        $response = null;
        switch ($this->gateway) {
            case "android" :
                // Preparar los objectos gcm y data para entregarselo al gateway
                $gcm = new \stdClass();
                $gcm->gateway = $this->urlGatewayAndroid;
                $gcm->gcmtoken = $this->gcmtoken;

                // Envio de la notificacion push
                $gcm = new Gcm($gcm);
                $response = $gcm->send($data);
                break;
            case "ios" :
                $ios = new \stdClass();
                $ios->gateway = $this->urlGatewayIos;
                $ios->iosPemFile = $this->iosPemFile;

                // Envio de la notificacion push
                $apns = new Ios($ios);
                $response = $apns->send($data);
                break;
        }

        return $response;
    }
}
