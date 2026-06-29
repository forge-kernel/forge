<?php

declare(strict_types=1);

namespace App\Http\Examples;

use Modules\ForgeMultiTenant\Attributes\TenantScope;
use Modules\ForgeNotification\Services\ForgeNotificationService;
use Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Attributes\Routable;
use Modules\ForgeRouter\Traits\ResponseHelper;
use Exception;

#[Routable(prefix: '/examples')]
#[TenantScope("central")]
#[UseMiddleware("web")]
final class Notifications
{
    use ResponseHelper;

    public function __construct(
        public readonly ForgeNotificationService $notification,
    ) {
    }

    #[Endpoint("/email")]
    public function testEmail(): Response
    {
        notify()->email()
            ->to('test@example.com')
            ->from('noreply@forge.test')
            ->subject('Test Email from Forge')
            ->body('This is a test email sent via ForgeNotification system!')
            ->send();
        return $this->jsonResponse(["message" => "Email message sent"]);
    }

    #[Endpoint("/notifications")]
    public function testNotifications(): Response
    {
        $results = [];

        try {
            // Test 1: Simple email notification (synchronous)
            notify()->email()
                ->to('test@example.com')
                ->from('noreply@forge.test')
                ->subject('Test Email from Forge')
                ->body('This is a test email sent via ForgeNotification system!')
                ->send();

            $results['email_sync'] = ['status' => 'success', 'message' => 'Email sent synchronously'];
        } catch (Exception $e) {
            $results['email_sync'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        try {
            // Test 2: HTML email notification
            notify()->email()
                ->to('test@example.com')
                ->from('noreply@forge.test')
                ->subject('Test HTML Email')
                ->html('<h1>Hello from Forge!</h1><p>This is an <strong>HTML</strong> email.</p>')
                ->send();

            $results['email_html'] = ['status' => 'success', 'message' => 'HTML email sent'];
        } catch (Exception $e) {
            $results['email_html'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        try {
            // Test 3: Queued email notification (asynchronous)
            notify()->email()
                ->to('test@example.com')
                ->from('noreply@forge.test')
                ->subject('Queued Email Test')
                ->body('This email was queued for async sending!')
                ->queue();

            $results['email_queued'] = ['status' => 'success', 'message' => 'Email queued for async sending'];
        } catch (Exception $e) {
            $results['email_queued'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        try {
            // Test 4: Email with CC and BCC
            notify()->email()
                ->to('test@example.com')
                ->from('noreply@forge.test')
                ->subject('Email with CC and BCC')
                ->body('This email has CC and BCC recipients')
                ->cc(['cc@example.com'])
                ->bcc(['bcc@example.com'])
                ->send();

            $results['email_cc_bcc'] = ['status' => 'success', 'message' => 'Email with CC/BCC sent'];
        } catch (Exception $e) {
            $results['email_cc_bcc'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        return $this->jsonResponse($results);
    }
}
