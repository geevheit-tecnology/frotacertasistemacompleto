<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Support\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            Company::query()
                ->where('group_id', $request->user()->group_id)
                ->orderBy('legal_name')
                ->paginate(25)
        );
    }

    public function store(Request $request, Auditor $auditor): JsonResponse
    {
        $data = $request->validate([
            'legal_name' => ['required', 'string', 'max:180'],
            'trade_name' => ['nullable', 'string', 'max:180'],
            'tax_id' => ['nullable', 'string', 'max:32'],
        ]);

        $company = Company::create($data + ['group_id' => $request->user()->group_id]);
        $auditor->recordCreated($request, $company, 'api.v1.companies.store');

        return response()->json($company, 201);
    }

    public function show(Request $request, string $company): JsonResponse
    {
        return response()->json($this->findScoped($request, $company));
    }

    public function update(Request $request, Auditor $auditor, string $company): JsonResponse
    {
        $model = $this->findScoped($request, $company);
        $before = $model->toArray();
        $data = $request->validate([
            'legal_name' => ['sometimes', 'string', 'max:180'],
            'trade_name' => ['nullable', 'string', 'max:180'],
            'tax_id' => ['nullable', 'string', 'max:32'],
        ]);

        $model->fill($data);
        $model->save();
        $auditor->recordUpdated($request, $model, $before, 'api.v1.companies.update');

        return response()->json($model);
    }

    private function findScoped(Request $request, string $id): Company
    {
        return Company::query()
            ->where('group_id', $request->user()->group_id)
            ->whereKey($id)
            ->firstOrFail();
    }
}
