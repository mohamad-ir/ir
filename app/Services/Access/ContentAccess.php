<?php

namespace App\Services\Access;

use App\Enums\AccessLevel;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Single decision point for "who may see this item". Phase 1 knows two
 * levels (everyone / registered members). Paid tiers, course enrolments or
 * webinar tickets can be added later by extending this class only.
 */
class ContentAccess
{
    public function canView(Model $content, ?User $user): bool
    {
        if ($user?->isStaff()) {
            return true;
        }

        $level = $content->access_level ?? AccessLevel::Public;
        $level = $level instanceof AccessLevel ? $level : AccessLevel::tryFrom((string) $level) ?? AccessLevel::Public;

        return match ($level) {
            AccessLevel::Public => true,
            AccessLevel::Registered => $user !== null,
        };
    }

    public function reason(Model $content): ?string
    {
        return ($content->access_level ?? null) === AccessLevel::Registered
            ? 'This material is available free to registered members.'
            : null;
    }
}
