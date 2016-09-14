<?php

namespace PushTester\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use PushTester\Push\NotificationPush;

class SendPushCommand extends Command
{
    const GATEWAY_IOS = "tls://gateway.push.apple.com";
    const GATEWAY_ANDROID = "https://android.googleapis.com/gcm/send";

    protected function configure()
    {
        $this->setName("send-push")
            ->setDescription("Send new push")
            ->addArgument('platform', InputArgument::REQUIRED, 'Platform to send')
            ->addArgument('token', InputArgument::REQUIRED, 'Device token')
            ->addOption('gcm-token', 'g', InputOption::VALUE_REQUIRED, 'GCM token', '')
            ->addOption('pem-file', 'p', InputOption::VALUE_REQUIRED, 'APNS PEM file', '')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $options = [
            'platform'  => strtolower($input->getArgument('platform')),
            'token'     => $input->getArgument('token'),
            'gateway'   => [
                'android'   => self::GATEWAY_ANDROID,
                'ios'       => self::GATEWAY_IOS,
                'gcmToken'  => $input->getOption('gcm-token'),
                'iosPemFile' => $input->getOption('pem-file')
            ]
        ];

        $this->processPush($options);
    }

    /**
     * @param $data
     * @return bool
     */
    private function processPush(array $options)
    {
        $platform = $options['platform'];
        $token = $options['token'];
        $gateway = $options['gateway'];

        $np = new NotificationPush($gateway);
        $np->setMessage('Test message');
        $np->setTypeAlert('Alert');
        $np->setGateway($platform);
        /*if (array_key_exists('sound', $data['message'])) {
            $np->setSound($data['message']['sound']);
        }*/

        // add notification token to NotificationPush object
        $np->addNotificationToken($token);

        return $np->send();
    }
}
