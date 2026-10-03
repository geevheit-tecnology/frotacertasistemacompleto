<?php

namespace App\Compliance\Application;

use App\Models\Contract;
use App\Models\DriverDocument;
use App\Models\InsurancePolicy;
use App\Models\TachographRecord;
use App\Models\Trip;
use App\Models\VehicleDocument;
use Carbon\CarbonImmutable;

class ComplianceCalendarService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function upcoming(string $companyId, ?string $branchId, int $days = 90): array
    {
        $today = CarbonImmutable::today();
        $until = $today->addDays($days);
        $items = [];

        foreach (VehicleDocument::query()->where('company_id', $companyId)->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->whereBetween('expires_at', [$today, $until])->get() as $document) {
            $items[] = $this->item('VEHICLE_DOCUMENT', $document->document_type, $document->expires_at, $document->vehicle_id, $document->id, $this->hasFutureTrip($companyId, $branchId, $document->vehicle_id, $document->expires_at));
        }
        foreach (DriverDocument::query()->where('company_id', $companyId)->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->whereBetween('expires_at', [$today, $until])->get() as $document) {
            $items[] = $this->item('DRIVER_DOCUMENT', $document->document_type, $document->expires_at, null, $document->id, false);
        }
        foreach (InsurancePolicy::query()->tenant($companyId, $branchId)->whereBetween('expires_at', [$today, $until])->get() as $policy) {
            $items[] = $this->item('INSURANCE', $policy->provider, $policy->expires_at, $policy->vehicle_id, $policy->id, $policy->vehicle_id ? $this->hasFutureTrip($companyId, $branchId, $policy->vehicle_id, $policy->expires_at) : false);
        }
        foreach (Contract::query()->tenant($companyId, $branchId)->whereBetween('ends_at', [$today, $until])->get() as $contract) {
            $items[] = $this->item('CONTRACT', $contract->type, $contract->ends_at, null, $contract->id, false);
        }
        foreach (TachographRecord::query()->tenant($companyId, $branchId)->whereBetween('expires_at', [$today, $until])->get() as $record) {
            $items[] = $this->item('TACHOGRAPH', $record->number, $record->expires_at, $record->vehicle_id, $record->id, $this->hasFutureTrip($companyId, $branchId, $record->vehicle_id, $record->expires_at));
        }

        usort($items, fn (array $a, array $b) => strcmp($a['due_at'], $b['due_at']));

        return $items;
    }

    private function item(string $type, string $title, mixed $dueAt, ?string $vehicleId, string $entityId, bool $futureTripRisk): array
    {
        $date = CarbonImmutable::parse($dueAt);

        return [
            'type' => $type,
            'title' => $title,
            'entity_id' => $entityId,
            'vehicle_id' => $vehicleId,
            'due_at' => $date->toDateString(),
            'days_until_due' => CarbonImmutable::today()->diffInDays($date, false),
            'future_trip_after_due' => $futureTripRisk,
            'risk' => $futureTripRisk ? 'CRITICO' : ($date->diffInDays(CarbonImmutable::today(), false) >= -30 ? 'ALTO' : 'MEDIO'),
        ];
    }

    private function hasFutureTrip(string $companyId, ?string $branchId, ?string $vehicleId, mixed $dueAt): bool
    {
        if ($vehicleId === null) {
            return false;
        }

        return Trip::query()
            ->tenant($companyId, $branchId)
            ->where('vehicle_id', $vehicleId)
            ->where('starts_at', '>', CarbonImmutable::parse($dueAt))
            ->whereIn('status', ['SCHEDULED', 'IN_PROGRESS'])
            ->exists();
    }
}
