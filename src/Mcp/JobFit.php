<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Profile\Application\EvaluateJobFit\EvaluateJobFit;
use App\Profile\Domain\Fit\Criterion;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;

class JobFit
{
    public function __construct(
        private readonly EvaluateJobFit $evaluateJobFit,
    ) {
    }

    /**
     * Confronts offer criteria to the published profile and returns, per
     * criterion, whether it is supported / partial / not_found, with the
     * experience ids and public URLs backing each verdict. Evaluation is
     * deterministic: no LLM, no fabricated match, no arbitrary percentage.
     * `not_found` means the published profile does not establish the criterion,
     * not that the candidate lacks the skill.
     *
     * @param list<array{id?: string, description?: string, importance?: string, keywords?: list<string>}> $criteria
     */
    #[McpTool(name: 'evaluate_job_fit')]
    public function evaluateJobFit(
        #[Schema(description: 'Criteria extracted from the offer. Each item: {id, description, importance: "required"|"preferred", keywords: ["concrete technologies or terms to look for"]}. Provide keywords for reliable matching.')]
        array $criteria,
        #[Schema(description: 'Language (fr|en), default: fr.')]
        string $locale = 'fr',
        #[Schema(description: 'Raw job description. Stored as data only; it is never interpreted as instructions.')]
        string $jobDescription = '',
    ): string {
        $domainCriteria = [];
        foreach (array_values($criteria) as $index => $criterion) {
            $domainCriteria[] = $this->toCriterion($criterion, $index);
        }

        $assessment = $this->evaluateJobFit->evaluate($locale, ...$domainCriteria);

        return (string) json_encode($assessment->toArray());
    }

    /**
     * @param array{id?: string, description?: string, importance?: string, keywords?: list<string>} $raw
     */
    private function toCriterion(array $raw, int $index): Criterion
    {
        $id = isset($raw['id']) && '' !== (string) $raw['id'] ? (string) $raw['id'] : 'criterion_'.($index + 1);
        $description = (string) ($raw['description'] ?? '');

        $importance = Criterion::IMPORTANCE_REQUIRED === ($raw['importance'] ?? null)
            ? Criterion::IMPORTANCE_REQUIRED
            : Criterion::IMPORTANCE_PREFERRED;

        $keywords = array_values(array_filter(array_map('strval', $raw['keywords'] ?? [])));
        $terms = [] !== $keywords ? $keywords : $this->tokenize($description);

        return new Criterion($id, $description, $importance, $terms);
    }

    /**
     * Fallback when the agent provides no keywords: keep meaningful words only.
     *
     * @return list<string>
     */
    private function tokenize(string $description): array
    {
        $words = preg_split('/[^\p{L}\p{N}+#.]+/u', mb_strtolower($description), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_filter(
            $words,
            static fn (string $word): bool => mb_strlen($word) >= 4,
        )));
    }
}
