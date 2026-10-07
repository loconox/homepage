<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Profile\Domain\Experience as ExperienceModel;
use App\Profile\Domain\ProfileRepositoryInterface;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;

class Experience
{
    private const MAX_LIMIT = 20;

    public function __construct(
        private readonly ProfileRepositoryInterface $profile,
    ) {
    }

    /**
     * Searches experiences by technology, responsibility or topic. Every result
     * points back to a public evidence anchor on the website. Returns an empty
     * list when nothing matches — never a fabricated result.
     */
    #[McpTool(name: 'search_experience')]
    public function searchExperience(
        #[Schema(description: 'Free-text query matched against roles, responsibilities and technologies.')]
        string $query,
        #[Schema(description: 'Language (fr|en), default: fr.')]
        string $locale = 'fr',
        #[Schema(description: 'Maximum number of results (1-20), default: 5.')]
        int $limit = 5,
    ): string {
        $query = trim($query);
        $limit = max(1, min(self::MAX_LIMIT, $limit));

        $direct = [];
        $related = [];

        if ('' !== $query) {
            foreach ($this->profile->findExperiences($locale) as $experience) {
                if (null !== ($tech = $this->matchingTechnology($experience, $query))) {
                    $direct[] = $this->result($experience, 'direct', sprintf('Technology used: %s.', $tech));
                } elseif (null !== ($claim = $this->matchingText($experience, $query))) {
                    $related[] = $this->result($experience, 'related', $claim);
                }
            }
        }

        $results = array_slice([...$direct, ...$related], 0, $limit);

        return (string) json_encode(['results' => $results, 'next_cursor' => null]);
    }

    private function matchingTechnology(ExperienceModel $experience, string $query): ?string
    {
        foreach ($experience->technologies as $tech) {
            if (false !== mb_stripos($tech, $query) || false !== mb_stripos($query, $tech)) {
                return $tech;
            }
        }

        return null;
    }

    private function matchingText(ExperienceModel $experience, string $query): ?string
    {
        if (false !== mb_stripos($experience->role, $query)) {
            return $experience->role;
        }

        foreach ($experience->highlights as $highlight) {
            if (false !== mb_stripos($highlight, $query)) {
                return $highlight;
            }
        }

        return null;
    }

    /**
     * @return array{id: string, title: string, relevance: string, evidence: list<array{claim: string, source_id: string, url: string}>}
     */
    private function result(ExperienceModel $experience, string $relevance, string $claim): array
    {
        return [
            'id' => $experience->id,
            'title' => sprintf('%s — %s', $experience->role, $experience->organization),
            'relevance' => $relevance,
            'evidence' => [[
                'claim' => $claim,
                'source_id' => $experience->id,
                'url' => $experience->sourceUrl,
            ]],
        ];
    }
}
