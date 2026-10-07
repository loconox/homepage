<?php

declare(strict_types=1);

namespace App\Tests\Profile\Application;

use App\Profile\Application\SendEmail\SendEmail;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;

final class SendEmailTest extends TestCase
{
    private MailerInterface $mailer;
    private SendEmail $sendEmail;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->sendEmail = new SendEmail($this->mailer);
    }

    public function testSendSimpleEmail(): void
    {
        $this->mailer->expects(self::once())->method('send');

        $this->sendEmail->send(
            'user@example.com',
            'Hello',
            'Test message',
        );
    }

    public function testBodySizeLimit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('body exceeds 5 MiB');

        $this->sendEmail->send(
            'user@example.com',
            'Hello',
            str_repeat('x', 5 * 1024 * 1024 + 1),
        );
    }

    public function testAttachmentsSizeLimit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('attachments size exceeds 10 MiB');

        $largeContent = base64_encode(str_repeat('x', 10 * 1024 * 1024 + 1));
        $attachments = [
            ['name' => 'large.bin', 'content' => $largeContent],
        ];

        $this->sendEmail->send(
            'user@example.com',
            'Hello',
            'Body',
            $attachments,
        );
    }

    public function testInvalidBase64(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64 content');

        $this->sendEmail->send(
            'user@example.com',
            'Hello',
            'Body',
            [['name' => 'file.txt', 'content' => 'not!!!base64']],
        );
    }

    public function testMissingAttachmentField(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must have "name" and "content" fields');

        $this->sendEmail->send(
            'user@example.com',
            'Hello',
            'Body',
            [['name' => 'file.txt']],
        );
    }
}
