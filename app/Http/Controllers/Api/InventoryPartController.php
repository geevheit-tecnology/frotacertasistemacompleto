<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\InventoryPart;
use App\Support\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryPartController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            InventoryPart::query()->tenant($request->user()->company_id, $request->user()->branch_id)->orderBy('name')->paginate(25)
        );
    }

    public function store(Request $request, Auditor $auditor): JsonResponse
    {
        $data = $this->validatedData($request);
        $part = DB::transaction(function () use ($request, $data, $auditor): InventoryPart {
            $part = InventoryPart::create($data + [
                'group_id' => $request->user()->group_id,
                'company_id' => $request->user()->company_id,
                'branch_id' => $data['branch_id'] ?? $request->user()->branch_id,
            ]);
            if ((float) $part->quantity_on_hand > 0.0) {
                InventoryMovement::create([
                    'group_id' => $part->group_id,
                    'company_id' => $part->company_id,
                    'branch_id' => $part->branch_id,
                    'inventory_part_id' => $part->id,
                    'type' => 'IN',
                    'quantity' => $part->quantity_on_hand,
                    'unit_cost' => $part->average_cost,
                    'reason' => 'Initial stock',
                ]);
            }
            $auditor->recordCreated($request, $part, 'api.v1.inventory-parts.store');
            return $part;
        });
        return response()->json($part, 201);
    }

    public function show(Request $request, string $inventoryPart): JsonResponse
    {
        return response()->json($this->findScoped($request, $inventoryPart));
    }

    public function update(Request $request, Auditor $auditor, string $inventoryPart): JsonResponse
    {
        $part = $this->findScoped($request, $inventoryPart);
        $before = $part->toArray();
        $part->fill($this->validatedData($request, true));
        $part->save();
        $auditor->recordUpdated($request, $part, $before, 'api.v1.inventory-parts.update');
        return response()->json($part);
    }

    public function destroy(Request $request, Auditor $auditor, string $inventoryPart): JsonResponse
    {
        $part = $this->findScoped($request, $inventoryPart);
        $before = $part->toArray();
        $part->delete();
        $auditor->recordDeleted($request, $part, $before, 'api.v1.inventory-parts.destroy');
        return response()->json(status: 204);
    }

    private function findScoped(Request $request, string $id): InventoryPart
    {
        return InventoryPart::query()->tenant($request->user()->company_id, $request->user()->branch_id)->whereKey($id)->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, bool $partial = false): array
    {
        return $request->validate([
            'branch_id' => ['nullable', 'uuid'],
            'sku' => ['nullable', 'string', 'max:80'],
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:160'],
            'unit' => ['nullable', 'string', 'max:20'],
            'quantity_on_hand' => ['nullable', 'numeric', 'min:0'],
            'minimum_quantity' => ['nullable', 'numeric', 'min:0'],
            'average_cost' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:32'],
        ]);
    }
}
