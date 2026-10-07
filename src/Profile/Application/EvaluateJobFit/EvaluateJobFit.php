<?php

declare(strict_types=1);

namespace App\Profile\Application\EvaluateJobFit;

use App\Profile\Domain\Experience;
use App\Profile\Domain\Fit\Criterion;
use App\Profile\Domain\Fit\JobFitEvaluator;
use App\Profile\Domain\ProfileRepositoryInterface;

/**
 * Use case behind the `evaluate_job_fit` MCP tool. Pure orchestration: it never
 * calls an LLM — the calling agent extracts criteria, this use case confronts
 * them to documented evidence deterministically.
 */
final class EvaluateJobFit
{
    public function __construct(
        private readonly ProfileRepositoryInterface $profile,
        private readonly JobFitEvaluator $evaluator,
    ) {
    }

    public function evaluate(string $locale, Criterion ...$criteria): JobFitAssessment
    {
        $experiences = $this->profile->findExperiences($locale);

        $results = $this->evaluator->evaluateAll($experiences, ...$criteria);

        $criteriaById = [];
        foreach ($criteria as $criterion) {
            $criteriaById[$criterion->id] = $criterion;
        }

        $experiencesById = [];
        foreach ($experiences as $experience) {
            $experiencesById[$experience->id] = $experience;
        }

        return new JobFitAssessment($results, $criteriaById, $experiencesById);
    }
}
