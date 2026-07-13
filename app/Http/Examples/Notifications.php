<?php

declare(strict_types=1);

namespace App\Http\Examples;

use App\Dto\InvitationDTO;
use Modules\ForgeMultiTenant\Attributes\TenantScope;
use Modules\ForgeNotification\Enums\NotificationChannel;
use Modules\ForgeNotification\Payload\EmailPayload;
use Modules\ForgeNotification\Traits\SendsNotifications;
use Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Attributes\Routable;
use Modules\ForgeRouter\Traits\ResponseHelper;
use Modules\ForgeTemplates\Traits\TemplateHelper;

#[Routable(prefix: '/examples')]
#[TenantScope("central")]
#[UseMiddleware("web")]
final class Notifications
{
    use ResponseHelper;
    use TemplateHelper;
    use SendsNotifications;

    #[Endpoint("/email")]
    public function testEmail(): Response
    {
        sendNotification(NotificationChannel::email, new EmailPayload(
            to: 'test@example.com',
            subject: 'Test Email from Forge',
            html: '<h1>Hello from Forge!</h1><p>This is a test email.</p>',
        ));

        return $this->jsonResponse(["message" => "Email message sent"]);
    }

    #[Endpoint("/notifications")]
    public function testNotifications(): Response
    {
        $results = [];

        try {
            // Test 1: Simple email notification
            sendNotification(NotificationChannel::email, new EmailPayload(
                to: 'test@example.com',
                subject: 'Test Email from Forge',
                text: 'This is a test email sent via ForgeNotification system!',
            ));

            $results['email_sync'] = ['status' => 'success', 'message' => 'Email sent'];
        } catch (\Throwable $e) {
            collect_exception($e);
            $results['email_sync'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        try {
            // Test 2: HTML email with template (array data)
            $html = $this->useTemplate('emails/welcome', [
                'name' => 'John Doe',
                'appName' => 'Forge',
                'supportEmail' => 'support@forge.test',
            ]);

            sendNotification(NotificationChannel::email, new EmailPayload(
                to: 'test@example.com',
                subject: 'Welcome to Forge!',
                html: $html,
            ));

            $results['email_template_array'] = ['status' => 'success', 'message' => 'Template email (array) sent'];
        } catch (\Throwable $e) {
            collect_exception($e);
            $results['email_template_array'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        try {
            // Test 3: HTML email with template (DTO data)
            $dto = new InvitationDTO(
                recipientName: 'Jane Smith',
                inviterName: 'John Doe',
                workspaceName: 'Acme Corp',
                inviteUrl: 'https://forge.test/invite/abc123',
            );

            $html = $this->useTemplate('emails/invitation', $dto);

            sendNotification(NotificationChannel::email, new EmailPayload(
                to: 'test@example.com',
                subject: 'You\'ve been invited to Acme Corp',
                html: $html,
            ));

            $results['email_template_dto'] = ['status' => 'success', 'message' => 'Template email (DTO) sent'];
        } catch (\Throwable $e) {
            collect_exception($e);
            $results['email_template_dto'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        return $this->jsonResponse($results);
    }
}
