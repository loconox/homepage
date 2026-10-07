<?php

declare(strict_types=1);

namespace App\Profile\Domain\Fit;

/**
 * Deterministic outcome of confronting one job criterion to the published
 * profile. Crucially, {@see FitStatus::NotFound} means "the published profile
 * does not establish this", NOT "the candidate lacks the skill".
 */
enum FitStatus: string
{
    case Supported = 'supported';
    case Partial = 'partial';
    case NotFound = 'not_found';
    case Conflicting = 'conflicting';
}
