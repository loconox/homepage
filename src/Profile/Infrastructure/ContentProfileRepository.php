<?php

declare(strict_types=1);

namespace App\Profile\Infrastructure;

use App\Content\ContentProvider;
use App\Profile\Domain\Experience;
use App\Profile\Domain\ProfileRepositoryInterface;
use App\Profile\Domain\Skill;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Adapts the existing YAML-backed {@see ContentProvider} to the domain port,
 * and computes the public evidence URL (an anchor on the website) for each
 * experience, per locale.
 */
final class ContentProfileRepository implements ProfileRepositoryInterface
{
    /** Path prefix per locale, matching the routes pre-rendered by the website. */
    private const LOCALE_PATH = ['fr' => '', 'en' => '/en'];

    public function __construct(
        private readonly ContentProvider $content,
        #[Autowire('%env(DEFAULT_URI)%')]
        private readonly string $baseUri,
    ) {
    }

    public function findExperiences(string $locale): array
    {
        return array_values(array_map(
            fn (array $raw): Experience => $this->mapExperience($raw, $locale),
            $this->content->getExperiences($locale),
        ));
    }

    public function findExperienceById(string $locale, string $id): ?Experience
    {
        foreach ($this->findExperiences($locale) as $experience) {
            if ($experience->id === $id) {
                return $experience;
            }
        }

        return null;
    }

    public function findSkills(string $locale): array
    {
        $experiences = $this->findExperiences($locale);
        $skills = [];

        foreach ($this->content->getSkills($locale) as $group) {
            $category = (string) $group['category'];
            foreach ($group['items'] as $name) {
                $name = (string) $name;
                $skills[] = new Skill(
                    id: $this->slugify($name),
                    name: $name,
                    category: $category,
                    experienceIds: $this->experienceIdsProving($name, $experiences),
                );
            }
        }

        return $skills;
    }

    /**
     * @param array<string, mixed> $raw
     */
    private function mapExperience(array $raw, string $locale): Experience
    {
        $id = (string) $raw['id'];

        return new Experience(
            id: $id,
            organization: (string) $raw['company'],
            role: (string) $raw['role'],
            companyType: isset($raw['companyType']) ? (string) $raw['companyType'] : null,
            startDate: isset($raw['start']) ? (string) $raw['start'] : null,
            endDate: isset($raw['end']) ? (string) $raw['end'] : null,
            highlights: array_values(array_map('strval', $raw['highlights'] ?? [])),
            technologies: array_values(array_map('strval', $raw['technologies'] ?? [])),
            sourceUrl: $this->sourceUrl($locale, $id),
        );
    }

    private function sourceUrl(string $locale, string $id): string
    {
        $path = self::LOCALE_PATH[$locale] ?? '';

        return rtrim($this->baseUri, '/').$path.'/#experience-'.$id;
    }

    /**
     * @param list<Experience> $experiences
     *
     * @return list<string>
     */
    private function experienceIdsProving(string $skill, array $experiences): array
    {
        $ids = [];
        foreach ($experiences as $experience) {
            if ($experience->hasTechnology($skill)) {
                $ids[] = $experience->id;
            }
        }

        return $ids;
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';

        return trim($value, '-');
    }
}
