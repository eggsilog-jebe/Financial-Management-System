<?php

declare(strict_types=1);

namespace App\DTOs\Accounting;

final readonly class ExecutiveScorecardData
{
    /**
     * @param array<ScorecardItemData> $items
     * @param array<string, array<ScorecardItemData>> $perspectives
     */
    public function __construct(
        public array $items,
        public array $perspectives,
        public int $totalMetrics,
        public int $optimalCount,
        public int $warningCount,
        public int $criticalCount,
        public float $compositeScorePct,
        public string $compositeGrade,
        public string $compositeBadgeColor,
    ) {}
}
