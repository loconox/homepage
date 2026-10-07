<?php

declare(strict_types=1);

namespace App\Profile\Domain;

/**
 * A professional experience, treated as the atomic unit of evidence:
 * every claim the server supports points back to one Experience and its
 * public {@see $sourceUrl} anchor on the website.
 */
final class Experience
{
    /**
     * @param list<string> $highlights   Documented responsibilities / achievements.
     * @param list<string> $technologies Technologies documented for this experience.
     */
    public function __construct(
        public readonly string $id,
        public readonly string $organization,
        public readonly string $role,
        public readonly ?string $companyType,
        public readonly ?string $startDate,
        public readonly ?string $endDate,
        public readonly array $highlights,
        public readonly array $technologies,
        public readonly string $sourceUrl,
        public readonly string $context = 'professional',
    ) {
    }

    public function isOngoing(): bool
    {
        return null === $this->endDate;
    }

    public function hasTechnology(string $technology): bool
    {
        foreach ($this->technologies as $tech) {
            if (0 === strcasecmp($tech, $technology)) {
                return true;
            }
        }

        return false;
    }
}
