<?php

declare(strict_types=1);

namespace FleetIntelligence\Administration\Domain;

final readonly class OrganizationScope
{
    public function __construct(
        public string $groupId,
        public string $companyId,
        public ?string $branchId,
        public UserRole $role,
    ) {
        if ($groupId === '' || $companyId === '') {
            throw new \InvalidArgumentException('Organization scope requires group and company.');
        }
    }
}
