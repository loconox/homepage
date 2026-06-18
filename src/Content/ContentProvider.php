<?php

declare(strict_types=1);

namespace App\Content;

final class ContentProvider
{
    /**
     * @param array{
     *     profile: array<string, mixed>,
     *     services: array<int, array<string, mixed>>,
     *     experiences: array<int, array<string, mixed>>,
     *     skills: array<int, array<string, mixed>>,
     *     education: array<int, array<string, mixed>>
     * } $content
     */
    public function __construct(private readonly array $content)
    {
    }

    /** @return array<string, mixed> */
    public function getProfile(): array
    {
        return $this->content['profile'];
    }

    /** @return array<int, array<string, mixed>> */
    public function getServices(): array
    {
        return $this->content['services'];
    }

    /** @return array<int, array<string, mixed>> */
    public function getExperiences(): array
    {
        return $this->content['experiences'];
    }

    /** @return array<int, array<string, mixed>> */
    public function getSkills(): array
    {
        return $this->content['skills'];
    }

    /** @return array<int, array<string, mixed>> */
    public function getEducation(): array
    {
        return $this->content['education'];
    }
}
