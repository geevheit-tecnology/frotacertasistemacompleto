<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\DriverDocumentController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\MissionControlController;
use App\Http\Controllers\Api\OilServiceRecordController;
use App\Http\Controllers\Api\InventoryPartController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\VehicleDocumentController;
use App\Http\Controllers\Api\WorkOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'health']);
Route::get('/ready', [HealthController::class, 'ready']);

Route::post('/v1/auth/login', [AuthController::class, 'login']);

Route::prefix('v1')->group(function (): void {
    Route::middleware('auth.api')->group(function (): void {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::apiResource('companies', CompanyController::class)->except(['destroy'])->middleware('permission:administration.manage');
        Route::apiResource('branches', BranchController::class)->middleware('permission:administration.manage');
        Route::apiResource('users', UserController::class)->middleware('permission:administration.manage');
        Route::apiResource('vehicles', VehicleController::class)->middleware('permission:fleet.manage');
        Route::apiResource('drivers', DriverController::class)->middleware('permission:fleet.manage');
        Route::apiResource('vehicle-documents', VehicleDocumentController::class)->middleware('permission:documents.manage');
        Route::apiResource('driver-documents', DriverDocumentController::class)->middleware('permission:documents.manage');
        Route::apiResource('oil-services', OilServiceRecordController::class)->middleware('permission:maintenance.manage');
        Route::apiResource('suppliers', SupplierController::class)->middleware('permission:maintenance.manage');
        Route::apiResource('inventory-parts', InventoryPartController::class)->middleware('permission:maintenance.manage');
        Route::apiResource('work-orders', WorkOrderController::class)->middleware('permission:maintenance.manage');
        Route::post('work-orders/{workOrder}/parts', [WorkOrderController::class, 'addPart'])->middleware('permission:maintenance.manage');
        Route::apiResource('alerts', AlertController::class)->only(['index', 'show', 'update'])->middleware('permission:mission-control.view');
        Route::get('mission-control', [MissionControlController::class, 'show'])->middleware('permission:mission-control.view');
    });
});
