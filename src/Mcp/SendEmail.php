<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Profile\Application\SendEmail\SendEmail as SendEmailUseCase;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;

class SendEmail
{
    public function __construct(
        private readonly SendEmailUseCase $sendEmailUseCase,
    ) {
    }

    /**
     * Send an email to the recruiter contact address.
     * Attachments must be base64-encoded. Total size limits: 5 MiB body + 10 MiB attachments.
     */
    #[McpTool(name: 'send_email')]
    public function sendEmail(
        #[Schema(description: 'Sender email address.')]
        string $from_email,
        #[Schema(description: 'Email subject.')]
        string $subject,
        #[Schema(description: 'Email body (plain text or HTML). Max 5 MiB.')]
        string $body,
        #[Schema(description: 'Optional base64-encoded attachments as array of {name: string, content: base64_string}.')]
        string $attachments_json = '[]',
    ): string {
        $attachments = json_decode($attachments_json, associative: true) ?? [];

        try {
            $this->sendEmailUseCase->send($from_email, $subject, $body, $attachments);
            return json_encode(['success' => true, 'message' => 'Email sent successfully']);
        } catch (\InvalidArgumentException $e) {
            return json_encode(['success' => false, 'error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'error' => 'Failed to send email']);
        }
    }
}
