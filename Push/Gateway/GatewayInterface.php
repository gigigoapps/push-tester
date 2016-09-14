<?php

namespace PushTester\Push\Gateway;

interface GatewayInterface
{
    /**
     * @param array $data
     * @return mixed
     */
    public function send(array $data);

    /**
     * @return string
     */
    public static function platform();
}