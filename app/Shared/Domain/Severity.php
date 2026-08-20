<?php

declare(strict_types=1);

namespace FleetIntelligence\Shared\Domain;

enum Severity: string
{
    case INFO = 'INFO';
    case ATENCAO = 'ATENCAO';
    case MEDIO = 'MEDIO';
    case ALTO = 'ALTO';
    case CRITICO = 'CRITICO';
}
