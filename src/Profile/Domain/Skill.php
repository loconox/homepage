<?php

declare(strict_types=1);

namespace App\Profile\Domain;

/**
 * A declared skill. The distinction the project cares about lives here:
 * a Skill is *declared* on the profile, but it is only *proven* when it is
 * linked to at least one {@see Experience} ({@see $experienceIds}).
 */
final class Skill
{
    /**
     * @param list<string> $experienceIds Ids of the experiences that document this skill.
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $category,
        public readonly array $experienceIds,
    ) {
    }

    public function isProven(): bool
    {
        return [] !== $this->experienceIds;
    }
}
