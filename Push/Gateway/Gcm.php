<?php

namespace PushTester\Push\Gateway;

/**
 * @author Gigigo
 */
class Gcm
{

    protected $apiUrl = "";
    protected $apiKey = "";
    protected $responses;

    /**
     * Contruct
     * @param StdClass ApplicationAndroid $app
     */
    public function __construct($app)
    {
        $this->apiUrl = $app->gateway;
        $this->apiKey = $app->gcmtoken;
        $this->responses = array();
    }

    /**
     * Method for send Notification Push
     * @param StdClass $data
     * @return Response Array
     */
    public function send($data)
    {

        $this->responses['device'] = array();
        $this->responses['technology'] = $data->technology;

        if (strlen($this->apiKey) > 0) {
            // Cabeceras para en envio de las notificaciones
            $headers = array(
                'Authorization: key=' . $this->apiKey,
                'Content-Type: application/json'
            );

            // Los datos que llegaran a los dispositivos mobiles
            $datatogcm = array(
                "message" => $data->message,
                "typeAlert" =>$data->typeAlert,
                "title" => "WhatsRed",
                "badge" => $data->badge,
                "sound" => $data->sound
            );

            // Los devices de envio (max array de 1000 elementos)
            $alldevices = array();
            foreach ($data->devices as $notificationToken) {
                $alldevices[] = $notificationToken;
            }
            $message = json_encode(array(
                "registration_ids" => $alldevices,
                "data" => $datatogcm
            ));

// 			error_log("Mensaje to GCM Request: " . $message);

            $body = "";
            try {
                $x = curl_init($this->apiUrl);
                curl_setopt($x, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($x, CURLOPT_HEADER, 1);
                curl_setopt($x, CURLOPT_POST, 1);
                curl_setopt($x, CURLOPT_POSTFIELDS, $message);
                curl_setopt($x, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($x, CURLOPT_SSL_VERIFYPEER, false);
                $response = curl_exec($x);
                $header_size = curl_getinfo($x, CURLINFO_HEADER_SIZE);
                $header = substr($response, 0, $header_size);
                $body = substr($response, $header_size);
                curl_close($x);
            } catch (Exception $e) {
                error_log($e);
            }

            $allerror = false;
            $allerrortext = "";
            if (strlen($body) > 0) {
                $message = json_decode($body);

//              error_log("========================");
// 		error_log("Response: ".$response);
// 		error_log("Message". print_r($message, true));
// 		error_log("Body". $body);
//              error_log("========================");

                if (preg_match("/unauthorized/i", $body)) {
                    error_log("Todas las notificaciones push de Android han sido desautorizadas [ERROR 401]");
                    $allerrortext = "Unauthorized [ERROR 401]";
                    $allerror = true;
                } else if ($message === null) {
                    error_log("No se ha enviado ninguna notificacion push de Android: [ERROR A] success = null");
                    $allerrortext = "No existe respuesta de " . $this->apiUrl . " [ERROR A]";
                    $allerror = true;
                } else if ($message->success == 0) {
                    error_log("No se ha enviado ninguna notificacion push de Android: [ERROR B] success = 0)");
                    $i = 0;
                    foreach ($message->results as $result) {
                        if (isset($data->devices[$i])) {
                            $this->responses['devices'][$i]['notificationToken'] = $data->devices[$i];
                            $this->responses['devices'][$i]['message'] = $result->error;
                            $this->responses['devices'][$i]['status'] = 2;
                        }
                        $i++;
                    }
                } else if (($message->failure > 0) || ($message->success > 0)) {
                    if ($message->failure > 0)
                        error_log($message->failure . " Android Notificaciones Push: [ERROR]");
                    if ($message->success > 0)
                        error_log($message->success . " Android Notificaciones Push: [OK]");
                    $i = 0;
                    foreach ($message->results as $result) {
                        if (isset($data->devices[$i])) {
                            $this->responses['devices'][$i]['notificationToken'] = $data->devices[$i];
                            $this->responses['devices'][$i]['message'] = (isset($result->message_id) ? $result->message_id : $result->error);
                            $this->responses['devices'][$i]['status'] = (isset($result->message_id) ? 0 : 2);
                        }
                        $i++;
                    }
                }
            } else {
                error_log("No se ha enviado ninguna notificacion push de Android: [ERROR C])");
                $allerror = true;
            }
            if ($allerror) {
                foreach ($data->devices as $key => $result) {
                    $this->responses['devices'][$key]['notificationToken'] = $data->devices[$key];
                    $this->responses['devices'][$key]['message'] = $allerrortext;
                    $this->responses['devices'][$key]['status'] = 2;
                }
            }
        } else {
            error_log("Configuracion GCM: " . $this->apiKey . " - URL: " . $this->apiUrl);
        }
        
//        error_log("==================");
//        error_log(print_r($this->responses['devices']));
//        error_log("==================");
        
        return $this->responses;
    }

}
