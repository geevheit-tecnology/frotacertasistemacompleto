<?php

declare(strict_types=1);

namespace FleetIntelligence\Shared\Domain;

final readonly class TenantScope
{
    public function __construct(
        public string $groupId,
        public string $companyId,
        public ?string $branchId = null,
    ) {
        if ($groupId === '' || $companyId === '') {
            throw new \InvalidArgumentException('Tenant scope requires group and company.');
        }
    }
}
