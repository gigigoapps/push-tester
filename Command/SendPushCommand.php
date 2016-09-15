<?php

namespace PushTester\Command;

use PushTester\Push\Gateway\ApnsGateway;
use PushTester\Push\Gateway\GcmGateway;
use PushTester\Push\NotificationPush;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SendPushCommand extends Command
{
    protected function configure()
    {
        $this->setName("send-push")
            ->setDescription("Send new push")
            ->setHelp(<<<HELP

This <info>send-push</info> command sends push notification.

  <info>console send-push [-m|--message MESSAGE] [-g|--gcm-token GCM-TOKEN] [-p|--pem-file PEM-FILE] [--] <platform> <token></info>

HELP
            )
            ->addArgument('platform', InputArgument::REQUIRED, 'Platform to send')
            ->addArgument('token', InputArgument::REQUIRED, 'Device token')
            ->addOption('message', 'm', InputOption::VALUE_REQUIRED, 'Message to send', null)
            ->addOption('gcm-token', 'g', InputOption::VALUE_REQUIRED, 'GCM token', '')
            ->addOption('pem-file', 'p', InputOption::VALUE_REQUIRED, 'APNS PEM file', '')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $options = [
            'platform'  => strtolower($input->getArgument('platform')),
            'token'     => $input->getArgument('token'),
            'message'   => $input->getOption('message'),
            'gateway'   => [
                GcmGateway::platform()  => $input->getOption('gcm-token'),
                ApnsGateway::platform() => $input->getOption('pem-file')
            ]
        ];

        $validPlatforms = array_keys($options['gateway']);

        if (! in_array($options['platform'], $validPlatforms)) {
            throw new \Exception("Invalid provided platform \"{$options['platform']}\". Only are valid: ".implode(', ', $validPlatforms));
        }

        $result = $this->processPush($options);

        $output->writeln("<info>$result</info>");
    }

    /**
     * @param array $options
     *  - platform: string
     *  - gateway: array
     *      - gcm: string
     *      - apns: string
     *  - message: string
     *  - token: string
     * @return string
     */
    private function processPush(array $options)
    {
        $platform = $options['platform'];
        $auth = $options['gateway'][$platform];
        $token = $options['token'];

        $np = new NotificationPush($platform, $auth);
        $np->addNotificationToken($token);

        $data = [];
        if (isset($options['message'])) {
            $data['message'] = $options['message'];
        }

        return $np->send($data);
    }
}
