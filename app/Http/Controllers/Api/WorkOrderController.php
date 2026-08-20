<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\InventoryPart;
use App\Models\Supplier;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderPart;
use App\Support\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            WorkOrder::query()
                ->tenant($request->user()->company_id, $request->user()->branch_id)
                ->with(['vehicle:id,plate', 'supplier:id,name'])
                ->latest('opened_at')
                ->paginate(25)
        );
    }

    public function store(Request $request, Auditor $auditor): JsonResponse
    {
        $data = $this->validatedData($request);
        $vehicle = $this->findVehicle($request, $data['vehicle_id']);
        if (isset($data['supplier_id'])) {
            $this->findSupplier($request, $data['supplier_id']);
        }

        $workOrder = WorkOrder::create($data + [
            'group_id' => $request->user()->group_id,
            'company_id' => $request->user()->company_id,
            'branch_id' => $vehicle->branch_id,
            'parts_cost' => 0,
            'total_cost' => $data['labor_cost'] ?? 0,
        ]);
        $auditor->recordCreated($request, $workOrder, 'api.v1.work-orders.store');
        return response()->json($workOrder->load(['vehicle', 'supplier', 'parts.inventoryPart']), 201);
    }

    public function show(Request $request, string $workOrder): JsonResponse
    {
        return response()->json($this->findScoped($request, $workOrder)->load(['vehicle', 'supplier', 'parts.inventoryPart']));
    }

    public function update(Request $request, Auditor $auditor, string $workOrder): JsonResponse
    {
        $model = $this->findScoped($request, $workOrder);
        $before = $model->toArray();
        $data = $this->validatedData($request, true);
        if (isset($data['vehicle_id'])) {
            $vehicle = $this->findVehicle($request, $data['vehicle_id']);
            $data['branch_id'] = $vehicle->branch_id;
        }
        if (isset($data['supplier_id'])) {
            $this->findSupplier($request, $data['supplier_id']);
        }
        $model->fill($data);
        $model->total_cost = (float) ($model->labor_cost ?? 0) + (float) $model->parts_cost;
        $model->save();
        $auditor->recordUpdated($request, $model, $before, 'api.v1.work-orders.update');
        return response()->json($model->load(['vehicle', 'supplier', 'parts.inventoryPart']));
    }

    public function addPart(Request $request, Auditor $auditor, string $workOrder): JsonResponse
    {
        $data = $request->validate([
            'inventory_part_id' => ['required', 'uuid'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);
        $order = $this->findScoped($request, $workOrder);
        $part = $this->findPart($request, $data['inventory_part_id']);

        if ((float) $part->quantity_on_hand < (float) $data['quantity']) {
            return response()->json(['message' => 'Insufficient inventory quantity.'], 422);
        }

        DB::transaction(function () use ($request, $auditor, $order, $part, $data): void {
            $unitCost = (float) ($data['unit_cost'] ?? $part->average_cost ?? 0);
            $quantity = (float) $data['quantity'];
            $line = WorkOrderPart::create([
                'work_order_id' => $order->id,
                'inventory_part_id' => $part->id,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $quantity * $unitCost,
            ]);
            $part->quantity_on_hand = (float) $part->quantity_on_hand - $quantity;
            $part->save();
            InventoryMovement::create([
                'group_id' => $order->group_id,
                'company_id' => $order->company_id,
                'branch_id' => $order->branch_id,
                'inventory_part_id' => $part->id,
                'work_order_id' => $order->id,
                'type' => 'OUT',
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'reason' => 'Work order '.$order->number,
            ]);
            $before = $order->toArray();
            $order->parts_cost = $order->parts()->sum('total_cost');
            $order->total_cost = (float) ($order->labor_cost ?? 0) + (float) $order->parts_cost;
            $order->save();
            $auditor->recordCreated($request, $line, 'api.v1.work-orders.parts.store');
            $auditor->recordUpdated($request, $order, $before, 'api.v1.work-orders.parts.store');
        });

        return response()->json($order->refresh()->load(['vehicle', 'supplier', 'parts.inventoryPart']));
    }

    private function findScoped(Request $request, string $id): WorkOrder
    {
        return WorkOrder::query()->tenant($request->user()->company_id, $request->user()->branch_id)->whereKey($id)->firstOrFail();
    }

    private function findVehicle(Request $request, string $id): Vehicle
    {
        return Vehicle::query()->tenant($request->user()->company_id, $request->user()->branch_id)->whereKey($id)->firstOrFail();
    }

    private function findSupplier(Request $request, string $id): Supplier
    {
        return Supplier::query()->tenant($request->user()->company_id, $request->user()->branch_id)->whereKey($id)->firstOrFail();
    }

    private function findPart(Request $request, string $id): InventoryPart
    {
        return InventoryPart::query()->tenant($request->user()->company_id, $request->user()->branch_id)->whereKey($id)->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, bool $partial = false): array
    {
        return $request->validate([
            'vehicle_id' => [$partial ? 'sometimes' : 'required', 'uuid'],
            'supplier_id' => ['nullable', 'uuid'],
            'number' => [$partial ? 'sometimes' : 'required', 'string', 'max:80'],
            'type' => ['nullable', 'string', 'max:40'],
            'status' => ['nullable', 'string', 'max:32'],
            'priority' => ['nullable', 'string', 'max:32'],
            'description' => [$partial ? 'sometimes' : 'required', 'string', 'max:5000'],
            'opened_at' => [$partial ? 'sometimes' : 'required', 'date'],
            'scheduled_at' => ['nullable', 'date'],
            'closed_at' => ['nullable', 'date'],
            'odometer_km' => ['nullable', 'integer', 'min:0'],
            'labor_cost' => ['nullable', 'numeric', 'min:0'],
        ]);
    }
}
