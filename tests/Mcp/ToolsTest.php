<?php

declare(strict_types=1);

namespace App\Tests\Mcp;

use App\Mcp\Contact;
use App\Mcp\Experience;
use App\Mcp\JobFit;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Contract-level checks of the MCP tool adapters: they must return structured,
 * evidence-backed JSON built from the real profile data.
 */
final class ToolsTest extends KernelTestCase
{
    /**
     * @return array<string, mixed>
     */
    private function decode(string $json): array
    {
        return json_decode($json, true, flags: JSON_THROW_ON_ERROR);
    }

    public function testSearchExperienceReturnsDirectMatchesWithEvidenceUrl(): void
    {
        self::bootKernel();
        $tool = self::getContainer()->get(Experience::class);

        $payload = $this->decode($tool->searchExperience('Symfony', 'fr', 3));

        self::assertNotEmpty($payload['results']);
        $ids = array_column($payload['results'], 'id');
        self::assertContains('egerie', $ids);
        self::assertSame('direct', $payload['results'][0]['relevance']);
        self::assertStringContainsString('#experience-', $payload['results'][0]['evidence'][0]['url']);
    }

    public function testSearchExperienceReturnsEmptyListWhenNothingMatches(): void
    {
        self::bootKernel();
        $tool = self::getContainer()->get(Experience::class);

        $payload = $this->decode($tool->searchExperience('nonexistent-technology-xyz', 'fr'));

        self::assertSame([], $payload['results']);
        self::assertNull($payload['next_cursor']);
    }

    public function testEvaluateJobFitIsHonestAboutAbsentTechnology(): void
    {
        self::bootKernel();
        $tool = self::getContainer()->get(JobFit::class);

        $payload = $this->decode($tool->evaluateJobFit([
            ['id' => 'symfony', 'importance' => 'required', 'keywords' => ['Symfony']],
            ['id' => 'rabbitmq', 'importance' => 'preferred', 'keywords' => ['RabbitMQ']],
        ], 'fr'));

        $status = [];
        foreach ($payload['criteria'] as $line) {
            $status[$line['criterion_id']] = $line['status'];
        }

        self::assertSame('supported', $status['symfony']);
        self::assertSame('not_found', $status['rabbitmq']);
        self::assertContains('rabbitmq', $payload['unknowns']);
    }

    public function testGetContactInfoExposesPublicChannelsOnly(): void
    {
        self::bootKernel();
        $tool = self::getContainer()->get(Contact::class);

        $payload = $this->decode($tool->getContactInfo('recruitment', 'fr'));

        self::assertArrayHasKey('email', $payload);
        self::assertArrayHasKey('linkedin_url', $payload);
        self::assertSame('email', $payload['preferred_contact_method']);
        self::assertArrayNotHasKey('phone', $payload);
    }
}
