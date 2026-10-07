<?php

declare(strict_types=1);

namespace App\Profile\Domain;

/**
 * Port exposing the profile to the application layer. The domain does not know
 * where the data comes from (YAML today) nor that MCP exists.
 */
interface ProfileRepositoryInterface
{
    /** @return list<Experience> */
    public function findExperiences(string $locale): array;

    public function findExperienceById(string $locale, string $id): ?Experience;

    /** @return list<Skill> */
    public function findSkills(string $locale): array;
}
