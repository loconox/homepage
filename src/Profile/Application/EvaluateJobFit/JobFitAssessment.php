<?php

declare(strict_types=1);

namespace App\Profile\Application\EvaluateJobFit;

use App\Profile\Domain\Experience;
use App\Profile\Domain\Fit\Criterion;
use App\Profile\Domain\Fit\CriterionResult;
use App\Profile\Domain\Fit\FitStatus;

/**
 * Result of an evaluation, shaped for a transport-agnostic, serializable output.
 * Deliberately offers no single "match percentage": the summary is a plain,
 * auditable count of statuses grouped by the importance the agent declared.
 */
final class JobFitAssessment
{
    /**
     * @param list<CriterionResult>    $results
     * @param array<string, Criterion> $criteriaById
     * @param array<string, Experience> $experiencesById
     */
    public function __construct(
        private readonly array $results,
        private readonly array $criteriaById,
        private readonly array $experiencesById,
    ) {
    }

    /**
     * @return array{
     *     criteria: list<array{criterion_id: string, status: string, importance: string, evidence_ids: list<string>, explanation: string}>,
     *     unknowns: list<string>,
     *     summary: array<string, array<string, int>>,
     *     sources: list<array{id: string, url: string}>
     * }
     */
    public function toArray(): array
    {
        $criteria = [];
        $unknowns = [];
        $summary = [];
        $evidenceIds = [];

        foreach ($this->results as $result) {
            $importance = $this->criteriaById[$result->criterionId]->importance;

            $criteria[] = [
                'criterion_id' => $result->criterionId,
                'status' => $result->status->value,
                'importance' => $importance,
                'evidence_ids' => $result->evidenceIds,
                'explanation' => $result->explanation,
            ];

            $summary[$importance][$result->status->value] = ($summary[$importance][$result->status->value] ?? 0) + 1;

            if (FitStatus::NotFound === $result->status) {
                $unknowns[] = $result->criterionId;
            }

            foreach ($result->evidenceIds as $id) {
                $evidenceIds[$id] = true;
            }
        }

        return [
            'criteria' => $criteria,
            'unknowns' => $unknowns,
            'summary' => $summary,
            'sources' => $this->sources(array_keys($evidenceIds)),
        ];
    }

    /**
     * @param list<string> $ids
     *
     * @return list<array{id: string, url: string}>
     */
    private function sources(array $ids): array
    {
        $sources = [];
        foreach ($ids as $id) {
            if (isset($this->experiencesById[$id])) {
                $sources[] = ['id' => $id, 'url' => $this->experiencesById[$id]->sourceUrl];
            }
        }

        return $sources;
    }
}
