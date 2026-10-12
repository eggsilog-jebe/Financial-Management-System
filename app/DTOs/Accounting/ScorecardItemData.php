<?php

declare(strict_types=1);

namespace App\DTOs\Accounting;

final readonly class ScorecardItemData
{
    public function __construct(
        public string $perspective,
        public string $metric,
        public string $target,
        public string $actual,
        public string $variance,
        public string $status,
        public string $statusLabel,
        public string $badgeColor,
        public string $icon,
        public ?float $progressPct = null,
        public ?string $description = null,
    ) {}
}
