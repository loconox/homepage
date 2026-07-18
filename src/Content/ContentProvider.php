<?php

declare(strict_types=1);

namespace App\Content;

use Symfony\Component\HttpFoundation\RequestStack;

final class ContentProvider
{
    private const DEFAULT_LOCALE = 'fr';

    /**
     * @param array<string, array{
     *     profile: array<string, mixed>,
     *     services: array<int, array<string, mixed>>,
     *     experiences: array<int, array<string, mixed>>,
     *     skills: array<int, array<string, mixed>>,
     *     education: array<int, array<string, mixed>>
     * }> $content Content indexed by locale.
     */
    public function __construct(
        private readonly array $content,
        private readonly RequestStack $requestStack,
    )
    {
    }

    /** @return array<string, mixed> */
    public function getProfile(): array
    {
        return $this->forLocale()['profile'];
    }

    /** @return array<int, array<string, mixed>> */
    public function getServices(): array
    {
        return $this->forLocale()['services'];
    }

    /** @return array<int, array<string, mixed>> */
    public function getExperiences(): array
    {
        return $this->forLocale()['experiences'];
    }

    /** @return array<int, array<string, mixed>> */
    public function getSkills(): array
    {
        return $this->forLocale()['skills'];
    }

    /** @return array<int, array<string, mixed>> */
    public function getEducation(): array
    {
        return $this->forLocale()['education'];
    }

    /**
     * @return array{
     *     profile: array<string, mixed>,
     *     services: array<int, array<string, mixed>>,
     *     experiences: array<int, array<string, mixed>>,
     *     skills: array<int, array<string, mixed>>,
     *     education: array<int, array<string, mixed>>
     * }
     */
    private function forLocale(): array
    {
        $locale = $this->requestStack->getCurrentRequest()?->getLocale() ?? self::DEFAULT_LOCALE;

        return $this->content[$locale] ?? $this->content[self::DEFAULT_LOCALE];
    }
}
