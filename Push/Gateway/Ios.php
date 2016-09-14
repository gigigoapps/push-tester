<?php

namespace PushTester\Push\Gateway;

/**
 * @author Gigigo
 */
class Ios
{

    protected $apiUrl = "";
    protected $apipem = "";
    protected $apiport = 2195;
    protected $responses;
    protected $isActive;

    /**
     * Contructor
     * @param StdClass $data
     */
    public function __construct($data)
    {
        $this->apiUrl = $data->gateway;
        $this->isActive = true;
        if (strlen($data->iosPemFile) > 1) {
            $this->apipem = $data->iosPemFile;
        } else {
            $this->isActive = false;
        }
// 		error_log("Configuracion PEM: ".$this->apipem." ".$this->apiUrl." ".$this->isActive);
// 		error_log(file_get_contents($this->apipem));
    }

    /**
     * Envia las notificaciones push
     * @param StdClass $data
     * @return multitype:
     */
    public function send($data)
    {

        $this->responses['devices'] = array();
        $this->responses['technology'] = $data->technology;
        $this->responses['stats'] = array();
        $this->responses['stats']['ok'] = 0;
        $this->responses['stats']['ko'] = 0;

        if ((strlen($this->apipem) > 0) && ($this->isActive)) {

            if (strlen($data->sound) == 0) $data->sound = "default";
            
            $payload = array();
            $payload['aps'] = array('alert' => $data->message, 'badge' => $data->badge, 'sound' => $data->sound, 'typeAlert' => $data->typeAlert);

// 			error_log("IOS: Payload ".print_r($payload, true));
            $payload = json_encode($payload);

            try {
                $stream_context = stream_context_create();
                stream_context_set_option($stream_context, 'ssl', 'local_cert', $this->apipem);
                $apns = stream_socket_client($this->apiUrl . ':' . $this->apiport, $error, $error_string, 5, STREAM_CLIENT_ASYNC_CONNECT, $stream_context);

                foreach ($data->devices as $key => $result) {
                    $apns_message = chr(0) . chr(0) . chr(32) . pack('H*', str_replace(' ', '', $data->devices[$key])) . chr(0) . chr(strlen($payload)) . $payload;
                    $this->responses['devices'][$key]['id'] = $data->devices[$key];
                    try {
                        if (!fwrite($apns, $apns_message) === FALSE) {
                            $this->responses['devices'][$key]['message'] = "OK";
                            $this->responses['stats']['ok'] ++;
                        } else {
                            $this->responses['devices'][$key]['message'] = "Not writed to the socket. " . $error . " " . $error_string;
                            $this->responses['stats']['ko'] ++;
                        }
                    } catch (Exception $e) {
                        error_log($e);
                        $this->responses['devices'][$key]['message'] = $e->getMessage();
                        $this->responses['stats']['ko'] ++;
                    }
                }
                if ($this->responses['stats']['ko'] > 0)
                    error_log(" IOS Notificaciones Push: [ERROR]");
                error_log($this->responses['stats']['ok'] . " IOS Notificaciones Push: [OK]");
            } catch (Exception $e) {
                error_log($e);
            }
        }
        return $this->responses;
    }

    /**
     * Method for get Apns Services Notifications Tokens expired.
     * @param String $applicationid
     * @return array
     */
    public function sendFeedbackAppleRequest()
    {
        //connect to the APNS feedback servers
        //make sure you're using the right dev/production server & cert combo!
        $feedback_tokens = array();
        //$dirios = "pem/".$applicationid;
        //$directory = __DIR__."/../../config/".$dirios."/";
        error_log("[Console::Work] [sendFeedbackAppleRequest] Processing...");

        $stream_context = stream_context_create();
        stream_context_set_option($stream_context, 'ssl', 'local_cert', $this->apipem);
        //stream_context_set_option($stream_context, 'ssl', 'local_cert', $directory . "/" . $filename);
        
        $apns = stream_socket_client('ssl://feedback.push.apple.com:2196', $errcode, $errstr, 60, STREAM_CLIENT_CONNECT, $stream_context);
        if(!$apns) {
            error_log(sprintf("[Console::Work] [sendFeedbackAppleRequest] Exception: %s - %s",$errcode, $errstr));
        } else {
            //and read the data on the connection:
            while(!feof($apns)) {
                $data = fread($apns, 38);
                if(strlen($data)) {
                    $feedback_tokens[] = unpack("N1timestamp/n1length/H*devtoken", $data);
                }
            }
            fclose($apns);
        }

        return $feedback_tokens;
    }

}
