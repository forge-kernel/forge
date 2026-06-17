<?php

declare(strict_types=1);

namespace App\Modules\ForgeNotification;

use Forge\Core\Config\Config;
use Forge\Core\Contracts\NotificationInterface;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;
use App\Modules\ForgeNotification\Services\ForgeNotificationService;
use Forge\Core\DI\Attributes\Service;
use Forge\CLI\Traits\OutputHelper;

#[Service]
#[Module(
    name: 'ForgeNotification',
    version: '0.3.0',
    description: 'Multi-channel notification system with provider support, fluent API, and async queue integration',
    order: 99,
    author: 'Forge Team',
    license: 'MIT',
    type: 'communication',
    tags: ['communication', 'notification', 'email', 'sms', 'push']
)]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-kernel/kernel-module-registry')]
#[ConfigDefaults(defaults: [
    'forge_notification' => [
        'default_channel' => 'email',
        'queue' => [
            'enabled' => true,
            'queue_name' => 'notifications',
            'priority' => 'normal',
            'max_retries' => 3,
            'delay' => '0s',
        ],
        'channels' => [
            'email' => [
                'default_provider' => 'smtp',
                'providers' => [
                    'smtp' => [
                        'host' => 'localhost',
                        'port' => 1025,
                        'username' => '',
                        'password' => '',
                        'encryption' => 'none',
                        'from_address' => 'noreply@forge.test',
                        'from_name' => 'Forge Application',
                    ],
                    'sendgrid' => [
                        'api_key' => '',
                        'from_address' => 'noreply@example.com',
                        'from_name' => 'Forge Application',
                    ],
                    'mailgun' => [
                        'domain' => '',
                        'api_key' => '',
                        'from_address' => 'noreply@example.com',
                        'from_name' => 'Forge Application',
                    ],
                ],
            ],
            'sms' => [
                'default_provider' => 'twilio',
                'providers' => [
                    'twilio' => [
                        'account_sid' => '',
                        'auth_token' => '',
                        'from' => '',
                    ],
                    'vonage' => [
                        'api_key' => '',
                        'api_secret' => '',
                        'from' => '',
                    ],
                ],
            ],
            'push' => [
                'default_provider' => 'firebase',
                'providers' => [
                    'firebase' => [
                        'server_key' => '',
                        'project_id' => '',
                    ],
                    'onesignal' => [
                        'app_id' => '',
                        'rest_api_key' => '',
                    ],
                ],
            ],
        ],
    ]
])]
final class ForgeNotificationModule
{
    use OutputHelper;

    public function register(Container $container): void
    {
        $this->setupConfigDefaults($container);
        $container->bind(NotificationInterface::class, ForgeNotificationService::class, true);
    }

    private function setupConfigDefaults(Container $container): void
    {
        /** @var Config $config */
        $config = $container->get(Config::class);
        $config->set('forge_notification.default_channel', env('NOTIFICATION_DEFAULT_CHANNEL', 'email'));
        $config->set('forge_notification.queue.enabled', env('NOTIFICATION_QUEUE_ENABLED', true));
        $config->set('forge_notification.queue.queue_name', env('NOTIFICATION_QUEUE_NAME', 'notifications'));
        $config->set('forge_notification.queue.priority', env('NOTIFICATION_QUEUE_PRIORITY', 'normal'));
        $config->set('forge_notification.queue.max_retries', env('NOTIFICATION_QUEUE_MAX_RETRIES', 3));
        $config->set('forge_notification.queue.delay', env('NOTIFICATION_QUEUE_DELAY', '0s'));
        $config->set('forge_notification.channels.email.default_provider', env('NOTIFICATION_EMAIL_PROVIDER', 'smtp'));
        $config->set('forge_notification.channels.email.providers.smtp.host', env('SMTP_HOST', 'localhost'));
        $config->set('forge_notification.channels.email.providers.smtp.port', env('SMTP_PORT', 1025));
        $config->set('forge_notification.channels.email.providers.smtp.username', env('SMTP_USERNAME', ''));
        $config->set('forge_notification.channels.email.providers.smtp.password', env('SMTP_PASSWORD', ''));
        $config->set('forge_notification.channels.email.providers.smtp.encryption', env('SMTP_ENCRYPTION', 'none'));
        $config->set('forge_notification.channels.email.providers.smtp.from_address', env('SMTP_FROM_ADDRESS', 'noreply@forge.test'));
        $config->set('forge_notification.channels.email.providers.smtp.from_name', env('SMTP_FROM_NAME', 'Forge Application'));
        $config->set('forge_notification.channels.email.providers.sendgrid.api_key', env('SENDGRID_API_KEY', ''));
        $config->set('forge_notification.channels.email.providers.sendgrid.from_address', env('SENDGRID_FROM_ADDRESS', 'noreply@example.com'));
        $config->set('forge_notification.channels.email.providers.sendgrid.from_name', env('SENDGRID_FROM_NAME', 'Forge Application'));
        $config->set('forge_notification.channels.email.providers.mailgun.domain', env('MAILGUN_DOMAIN', ''));
        $config->set('forge_notification.channels.email.providers.mailgun.api_key', env('MAILGUN_API_KEY', ''));
        $config->set('forge_notification.channels.email.providers.mailgun.from_address', env('MAILGUN_FROM_ADDRESS', 'noreply@example.com'));
        $config->set('forge_notification.channels.email.providers.mailgun.from_name', env('MAILGUN_FROM_NAME', 'Forge Application'));
        $config->set('forge_notification.channels.sms.default_provider', env('NOTIFICATION_SMS_PROVIDER', 'twilio'));
        $config->set('forge_notification.channels.sms.providers.twilio.account_sid', env('TWILIO_ACCOUNT_SID', ''));
        $config->set('forge_notification.channels.sms.providers.twilio.auth_token', env('TWILIO_AUTH_TOKEN', ''));
        $config->set('forge_notification.channels.sms.providers.twilio.from', env('TWILIO_FROM', ''));
        $config->set('forge_notification.channels.sms.providers.vonage.api_key', env('VONAGE_API_KEY', ''));
        $config->set('forge_notification.channels.sms.providers.vonage.api_secret', env('VONAGE_API_SECRET', ''));
        $config->set('forge_notification.channels.sms.providers.vonage.from', env('VONAGE_FROM', ''));
        $config->set('forge_notification.channels.push.default_provider', env('NOTIFICATION_PUSH_PROVIDER', 'firebase'));
        $config->set('forge_notification.channels.push.providers.firebase.server_key', env('FIREBASE_SERVER_KEY', ''));
        $config->set('forge_notification.channels.push.providers.firebase.project_id', env('FIREBASE_PROJECT_ID', ''));
        $config->set('forge_notification.channels.push.providers.onesignal.app_id', env('ONESIGNAL_APP_ID', ''));
        $config->set('forge_notification.channels.push.providers.onesignal.rest_api_key', env('ONESIGNAL_REST_API_KEY', ''));
    }
}
