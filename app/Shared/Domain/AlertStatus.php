<?php

declare(strict_types=1);

namespace FleetIntelligence\Shared\Domain;

enum AlertStatus: string
{
    case ABERTO = 'ABERTO';
    case VISUALIZADO = 'VISUALIZADO';
    case EM_ANALISE = 'EM_ANALISE';
    case EM_EXECUCAO = 'EM_EXECUCAO';
    case RESOLVIDO = 'RESOLVIDO';
    case IGNORADO = 'IGNORADO';
    case CANCELADO = 'CANCELADO';
}
