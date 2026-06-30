<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeadStage;
use App\Services\LookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLeadStageController extends Controller
{
    public function index(): JsonResponse
    {
        $stages = LeadStage::orderBy('id')->get();

        return $this->success($stages);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $stage = LeadStage::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
        ]);

        $stage->update($data);

        LookupService::forgetLeadStagesLookupCache();

        return $this->success($stage->fresh());
    }
}
