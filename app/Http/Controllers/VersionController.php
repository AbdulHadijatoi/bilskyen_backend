<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class VersionController extends Controller
{
    /**
     * Get application version
     */
    public function getVersion(): JsonResponse
    {
        return response()->json([
            'version' => config('app.version', '1.0.0'),
        ]);
    }
}


