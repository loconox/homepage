<?php

declare(strict_types=1);

namespace App\Profile\Domain\Fit;

final class CriterionResult
{
    /**
     * @param list<string> $evidenceIds Ids of the experiences backing the status.
     */
    public function __construct(
        public readonly string $criterionId,
        public readonly FitStatus $status,
        public readonly array $evidenceIds,
        public readonly string $explanation,
    ) {
    }
}
