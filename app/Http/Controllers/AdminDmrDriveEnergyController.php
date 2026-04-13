<?php

namespace App\Http\Controllers;

use App\Models\DmrDriveEnergy;
use Illuminate\Http\JsonResponse;

class AdminDmrDriveEnergyController extends Controller
{
    public function index(): JsonResponse
    {
        $items = DmrDriveEnergy::query()
            ->orderBy('name')
            ->get(['id', 'type_nummer', 'name']);

        return $this->success($items);
    }
}

