<?php

declare(strict_types=1);

namespace FleetIntelligence\Administration\Domain;

enum UserRole: string
{
    case OWNER = 'OWNER';
    case ADMIN = 'ADMIN';
    case FLEET_MANAGER = 'FLEET_MANAGER';
    case MAINTENANCE_MANAGER = 'MAINTENANCE_MANAGER';
    case DRIVER = 'DRIVER';
    case AUDITOR = 'AUDITOR';
}
