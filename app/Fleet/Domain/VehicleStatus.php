<?php

declare(strict_types=1);

namespace FleetIntelligence\Fleet\Domain;

enum VehicleStatus: string
{
    case ACTIVE = 'ACTIVE';
    case STOPPED = 'STOPPED';
    case BLOCKED = 'BLOCKED';
    case MAINTENANCE = 'MAINTENANCE';
    case INACTIVE = 'INACTIVE';
}
