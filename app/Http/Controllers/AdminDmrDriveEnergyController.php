<?php

namespace App\Http\Controllers;

use App\Models\DmrDriveEnergy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDmrDriveEnergyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $limit = (int) $request->get('limit', 200);
        if ($limit <= 0) {
            $limit = 200;
        }

        $items = DmrDriveEnergy::query()
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'type_nummer', 'name']);

        return $this->success($items);
    }
}

