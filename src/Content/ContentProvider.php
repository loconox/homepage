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
    public function getProfile(?string $locale = null): array
    {
        return $this->forLocale($locale)['profile'];
    }

    /** @return array<string, mixed> */
    public function getJob(?string $locale = null): array
    {
        return $this->forLocale($locale)['job'];
    }

    /** @return array<int, array<string, mixed>> */
    public function getServices(?string $locale = null): array
    {
        return $this->forLocale($locale)['services'];
    }

    /** @return array<int, array<string, mixed>> */
    public function getExperiences(?string $locale = null): array
    {
        return $this->forLocale($locale)['experiences'];
    }

    /** @return array<int, array<string, mixed>> */
    public function getSkills(?string $locale = null): array
    {
        return $this->forLocale($locale)['skills'];
    }

    /** @return array<int, array<string, mixed>> */
    public function getEducation(?string $locale = null): array
    {
        return $this->forLocale($locale)['education'];
    }

    /**
     * Locales for which content is available (e.g. ['fr', 'en']).
     *
     * @return list<string>
     */
    public function getLocales(): array
    {
        return array_keys($this->content);
    }

    /**
     * Resolves the content bundle for the given locale. When no locale is passed
     * it falls back to the current request locale, then to the default locale —
     * this keeps the request-driven rendering of the website working unchanged,
     * while letting request-less callers (CLI, MCP) select a locale explicitly.
     *
     * @return array{
     *     profile: array<string, mixed>,
     *     services: array<int, array<string, mixed>>,
     *     experiences: array<int, array<string, mixed>>,
     *     skills: array<int, array<string, mixed>>,
     *     education: array<int, array<string, mixed>>
     * }
     */
    private function forLocale(?string $locale = null): array
    {
        $locale ??= $this->requestStack->getCurrentRequest()?->getLocale() ?? self::DEFAULT_LOCALE;

        return $this->content[$locale] ?? $this->content[self::DEFAULT_LOCALE];
    }
}
