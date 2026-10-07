<?php

declare(strict_types=1);

namespace App\Tests\Profile\Application;

use App\Profile\Application\EvaluateJobFit\EvaluateJobFit;
use App\Profile\Domain\Fit\Criterion;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Non-regression suite over the REAL profile data (config/content.fr.yaml).
 * It pins the documented capabilities AND the documented limits of the
 * evaluation: absent technologies must surface as `not_found`, never fabricated.
 */
final class ReferenceOfferRegressionTest extends KernelTestCase
{
    private EvaluateJobFit $evaluateJobFit;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->evaluateJobFit = self::getContainer()->get(EvaluateJobFit::class);
    }

    /**
     * @param array<string, Criterion> $criteria
     *
     * @return array<string, array{status: string, evidence_ids: list<string>}>
     */
    private function assess(array $criteria): array
    {
        $assessment = $this->evaluateJobFit->evaluate('fr', ...array_values($criteria));
        $indexed = [];
        foreach ($assessment->toArray()['criteria'] as $line) {
            $indexed[$line['criterion_id']] = [
                'status' => $line['status'],
                'evidence_ids' => $line['evidence_ids'],
            ];
        }

        return $indexed;
    }

    public function testSymfonyDeveloperOffer(): void
    {
        $results = $this->assess([
            'symfony' => new Criterion('symfony', 'Expérience Symfony', Criterion::IMPORTANCE_REQUIRED, ['Symfony']),
            'api_platform' => new Criterion('api_platform', 'API Platform', Criterion::IMPORTANCE_REQUIRED, ['API Platform']),
            'docker' => new Criterion('docker', 'Conteneurisation', Criterion::IMPORTANCE_PREFERRED, ['Docker']),
            'rabbitmq' => new Criterion('rabbitmq', 'Messagerie asynchrone RabbitMQ', Criterion::IMPORTANCE_PREFERRED, ['RabbitMQ']),
        ]);

        self::assertSame('supported', $results['symfony']['status']);
        self::assertContains('egerie', $results['symfony']['evidence_ids']);
        self::assertSame('supported', $results['api_platform']['status']);
        self::assertContains('egerie', $results['api_platform']['evidence_ids']);
        self::assertSame('supported', $results['docker']['status']);

        // Honesty check: RabbitMQ is nowhere in the data → must NOT be fabricated.
        self::assertSame('not_found', $results['rabbitmq']['status']);
        self::assertSame([], $results['rabbitmq']['evidence_ids']);
    }

    public function testTechLeadOffer(): void
    {
        $results = $this->assess([
            'symfony' => new Criterion('symfony', 'Symfony', Criterion::IMPORTANCE_REQUIRED, ['Symfony']),
            'leadership' => new Criterion('leadership', 'Coordination et accompagnement d\'équipe', Criterion::IMPORTANCE_REQUIRED, ['Coordination', 'Accompagnement']),
            'hexagonal' => new Criterion('hexagonal', 'Architecture hexagonale', Criterion::IMPORTANCE_PREFERRED, ['Architecture Hexagonale']),
        ]);

        self::assertSame('supported', $results['symfony']['status']);

        // Leadership and hexagonal architecture are documented only in free-text
        // highlights, not as technologies → partial, with EGERIE as evidence.
        self::assertSame('partial', $results['leadership']['status']);
        self::assertContains('egerie', $results['leadership']['evidence_ids']);
        self::assertSame('partial', $results['hexagonal']['status']);
        self::assertContains('egerie', $results['hexagonal']['evidence_ids']);
    }

    public function testEngineeringManagerOfferSurfacesGaps(): void
    {
        $results = $this->assess([
            'architecture' => new Criterion('architecture', 'Culture technique', Criterion::IMPORTANCE_PREFERRED, ['Symfony', 'API Platform']),
            'people_management' => new Criterion('people_management', 'Management RH, recrutement', Criterion::IMPORTANCE_REQUIRED, ['recrutement', 'people management']),
            'budget' => new Criterion('budget', 'Gestion de budget / P&L', Criterion::IMPORTANCE_REQUIRED, ['budget', 'P&L']),
        ]);

        self::assertSame('supported', $results['architecture']['status']);

        // The published profile does not establish people management or budget
        // ownership → honest `not_found`, not a negative claim about the candidate.
        self::assertSame('not_found', $results['people_management']['status']);
        self::assertSame('not_found', $results['budget']['status']);
    }
}
