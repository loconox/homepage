<?php

declare(strict_types=1);

namespace App\Profile\Application\SendEmail;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

final class SendEmail
{
    private const MAX_BODY_SIZE = 5 * 1024 * 1024; // 5 MiB
    private const MAX_ATTACHMENTS_SIZE = 10 * 1024 * 1024; // 10 MiB
    private const RECIPIENT = 'job@jeremielibeau.fr';

    public function __construct(
        private readonly MailerInterface $mailer,
    ) {
    }

    /**
     * @param array<array{name: string, content: string}> $attachments Base64-encoded attachments
     *
     * @throws \InvalidArgumentException
     */
    public function send(string $fromEmail, string $subject, string $body, array $attachments = []): void
    {
        // Validate body size
        if (strlen($body) > self::MAX_BODY_SIZE) {
            throw new \InvalidArgumentException('Email body exceeds 5 MiB limit');
        }

        // Validate attachments size
        $totalAttachmentsSize = 0;
        $decodedAttachments = [];

        foreach ($attachments as $attachment) {
            if (!isset($attachment['name'], $attachment['content'])) {
                throw new \InvalidArgumentException('Each attachment must have "name" and "content" fields');
            }

            $decoded = base64_decode($attachment['content'], strict: true);
            if ($decoded === false) {
                throw new \InvalidArgumentException('Invalid base64 content in attachment');
            }

            $size = strlen($decoded);
            $totalAttachmentsSize += $size;

            if ($totalAttachmentsSize > self::MAX_ATTACHMENTS_SIZE) {
                throw new \InvalidArgumentException('Total attachments size exceeds 10 MiB limit');
            }

            $decodedAttachments[] = [
                'name' => $attachment['name'],
                'content' => $decoded,
            ];
        }

        // Build email
        $email = (new Email())
            ->from($fromEmail)
            ->to(self::RECIPIENT)
            ->subject($subject)
            ->text($body);

        // Add attachments
        foreach ($decodedAttachments as $attachment) {
            $email->addPart(new DataPart($attachment['content'], $attachment['name']));
        }

        $this->mailer->send($email);
    }
}
