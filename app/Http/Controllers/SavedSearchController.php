<?php

namespace App\Http\Controllers;

use App\Models\SavedSearch;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Saved Search Controller for Dealer
 */
class SavedSearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $searches = SavedSearch::where('user_id', $request->user()->id)
            ->paginate($request->get('limit', 15));

        return $this->paginated($searches);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'filters' => 'required|array',
        ]);

        $search = SavedSearch::create([
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'filters' => $request->filters,
        ]);

        return $this->created($search);
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        SavedSearch::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->delete();

        return $this->noContent();
    }
}

