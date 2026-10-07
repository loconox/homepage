<?php

declare(strict_types=1);

namespace App\Profile\Domain\Fit;

use App\Profile\Domain\Experience;

/**
 * Pure, deterministic evaluation engine. Given the published experiences and a
 * criterion, it decides whether the profile *documents* the criterion — it
 * never guesses. Two tiers of evidence:
 *
 *  - strong: a criterion term matches a first-class technology of an experience
 *    → {@see FitStatus::Supported};
 *  - weak: a term only appears in the free-text responsibilities/highlights
 *    → {@see FitStatus::Partial};
 *  - nothing → {@see FitStatus::NotFound}.
 *
 * {@see FitStatus::Conflicting} is part of the contract but not inferable from
 * the current single-source data, so it is never emitted here.
 */
final class JobFitEvaluator
{
    /**
     * @param list<Experience> $experiences
     */
    public function evaluate(array $experiences, Criterion $criterion): CriterionResult
    {
        $strong = [];
        $weak = [];

        foreach ($experiences as $experience) {
            if ($this->matchesTechnology($experience, $criterion->terms)) {
                $strong[] = $experience;
            } elseif ($this->matchesHighlights($experience, $criterion->terms)) {
                $weak[] = $experience;
            }
        }

        if ([] !== $strong) {
            return new CriterionResult(
                $criterion->id,
                FitStatus::Supported,
                $this->ids($strong),
                $this->explain('Documented as a technology in: %s.', $strong),
            );
        }

        if ([] !== $weak) {
            return new CriterionResult(
                $criterion->id,
                FitStatus::Partial,
                $this->ids($weak),
                $this->explain('Mentioned in the responsibilities of: %s.', $weak),
            );
        }

        return new CriterionResult(
            $criterion->id,
            FitStatus::NotFound,
            [],
            'No published evidence establishes this criterion.',
        );
    }

    /**
     * @param list<Experience> $experiences
     *
     * @return list<CriterionResult>
     */
    public function evaluateAll(array $experiences, Criterion ...$criteria): array
    {
        return array_map(
            fn (Criterion $criterion): CriterionResult => $this->evaluate($experiences, $criterion),
            $criteria,
        );
    }

    /**
     * @param list<string> $terms
     */
    private function matchesTechnology(Experience $experience, array $terms): bool
    {
        foreach ($terms as $term) {
            if ('' !== $term && $experience->hasTechnology($term)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string> $terms
     */
    private function matchesHighlights(Experience $experience, array $terms): bool
    {
        foreach ($terms as $term) {
            if ('' === $term) {
                continue;
            }
            foreach ($experience->highlights as $highlight) {
                if (false !== mb_stripos($highlight, $term)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param list<Experience> $experiences
     *
     * @return list<string>
     */
    private function ids(array $experiences): array
    {
        return array_values(array_map(
            static fn (Experience $experience): string => $experience->id,
            $experiences,
        ));
    }

    /**
     * @param list<Experience> $experiences
     */
    private function explain(string $template, array $experiences): string
    {
        $organizations = array_map(
            static fn (Experience $experience): string => $experience->organization,
            $experiences,
        );

        return sprintf($template, implode(', ', $organizations));
    }
}
