<?php

declare(strict_types=1);

namespace App\Profile\Domain\Fit;

/**
 * A single requirement extracted from a job offer by the calling agent.
 *
 * {@see $terms} are the concrete things to look for in the profile (technology
 * names, keywords). Keeping them explicit makes evaluation deterministic and
 * testable, and keeps the free-text job description as data, never as an
 * instruction the server trusts.
 */
final class Criterion
{
    public const IMPORTANCE_REQUIRED = 'required';
    public const IMPORTANCE_PREFERRED = 'preferred';

    /**
     * @param list<string> $terms
     */
    public function __construct(
        public readonly string $id,
        public readonly string $description,
        public readonly string $importance,
        public readonly array $terms,
    ) {
    }

    public function isRequired(): bool
    {
        return self::IMPORTANCE_REQUIRED === $this->importance;
    }
}
