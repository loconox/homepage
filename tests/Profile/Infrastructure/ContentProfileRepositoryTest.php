<?php

declare(strict_types=1);

namespace App\Tests\Profile\Infrastructure;

use App\Content\ContentProvider;
use App\Profile\Infrastructure\ContentProfileRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;

final class ContentProfileRepositoryTest extends TestCase
{
    private function repository(): ContentProfileRepository
    {
        $content = [
            'en' => [
                'experiences' => [
                    [
                        'id' => 'egerie',
                        'company' => 'EGERIE',
                        'companyType' => 'Scale-up',
                        'role' => 'Tech Lead',
                        'start' => 'Mar 2023',
                        'end' => 'Dec 2025',
                        'highlights' => ['Design of a hexagonal architecture'],
                        'technologies' => ['PHP', 'Symfony', 'API Platform'],
                    ],
                    [
                        'id' => 'astrolabe',
                        'company' => 'Astrolabe',
                        'companyType' => 'CAE',
                        'role' => 'Developer',
                        'start' => 'Apr 2026',
                        'highlights' => ['PHP backend'],
                        'technologies' => ['PHP', 'Symfony'],
                    ],
                ],
                'skills' => [
                    ['category' => 'Backend', 'items' => ['Symfony', 'Zend']],
                    ['category' => 'Architecture', 'items' => ['Hexagonal Architecture']],
                ],
            ],
        ];

        // Trailing slash on the base URI is intentional: the repository must trim it.
        return new ContentProfileRepository(
            new ContentProvider($content, new RequestStack()),
            'https://jeremielibeau.fr/',
        );
    }

    public function testMapsExperiencesWithLocaleAwareEvidenceUrl(): void
    {
        $experiences = $this->repository()->findExperiences('en');

        self::assertCount(2, $experiences);

        $egerie = $experiences[0];
        self::assertSame('egerie', $egerie->id);
        self::assertSame('EGERIE', $egerie->organization);
        self::assertSame('https://jeremielibeau.fr/en/#experience-egerie', $egerie->sourceUrl);
        self::assertFalse($egerie->isOngoing());
        self::assertTrue($egerie->hasTechnology('symfony'), 'technology match must be case-insensitive');
    }

    public function testOngoingExperienceHasNoEndDate(): void
    {
        $astrolabe = $this->repository()->findExperienceById('en', 'astrolabe');

        self::assertNotNull($astrolabe);
        self::assertTrue($astrolabe->isOngoing());
    }

    public function testFindExperienceByUnknownIdReturnsNull(): void
    {
        self::assertNull($this->repository()->findExperienceById('en', 'does-not-exist'));
    }

    public function testSkillIsProvenOnlyWhenLinkedToExperienceTechnology(): void
    {
        $skills = [];
        foreach ($this->repository()->findSkills('en') as $skill) {
            $skills[$skill->id] = $skill;
        }

        // Proven: Symfony appears in both experiences' technologies.
        self::assertSame(['egerie', 'astrolabe'], $skills['symfony']->experienceIds);
        self::assertTrue($skills['symfony']->isProven());

        // Declared but not proven: no experience documents Zend here.
        self::assertSame([], $skills['zend']->experienceIds);
        self::assertFalse($skills['zend']->isProven());

        // Multi-word skill is slugified and left unproven when absent from technologies.
        self::assertArrayHasKey('hexagonal-architecture', $skills);
        self::assertFalse($skills['hexagonal-architecture']->isProven());
    }
}
