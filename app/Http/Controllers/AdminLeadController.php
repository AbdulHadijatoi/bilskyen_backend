<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Services\Marketing\TrafficAttributionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin Lead Controller — browse all dealer leads (not scoped to a single dealer).
 */
class AdminLeadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Lead::with([
            'vehicle:id,title,registration,slug,dealer_id',
            'buyerUser:id,name,email,phone',
            'dealer:id,user_id,cvr,city,slug',
            'dealer.owner:id,name,email',
            'assignedUser:id,name,email',
            'leadStage:id,name',
            'leadIntent:id,name',
            'leadCategory:id,name',
            'source:id,name',
            'enquiry:id,lead_id,name,email,phone,subject,message,type,status',
        ]);

        if ($request->filled('dealer_id')) {
            $query->where('dealer_id', $request->integer('dealer_id'));
        }

        if ($request->filled('stage_id')) {
            $query->where('lead_stage_id', $request->integer('stage_id'));
        }

        if ($request->filled('category_id')) {
            $query->where('lead_category_id', $request->integer('category_id'));
        }

        if ($request->filled('traffic_source')) {
            $trafficSource = (string) $request->input('traffic_source');
            if (in_array($trafficSource, [TrafficAttributionService::SOURCE_META, TrafficAttributionService::SOURCE_OTHER], true)) {
                $query->effectiveTrafficSource($trafficSource);
            }
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('buyerUser', function ($buyer) use ($search) {
                        $buyer->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                        ->orWhereHas('enquiry', function ($enquiry) use ($search) {
                            $enquiry->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        })
                        ->orWhereHas('vehicle', function ($vehicle) use ($search) {
                            $vehicle->where('title', 'like', "%{$search}%")
                                ->orWhere('registration', 'like', "%{$search}%");
                        })
                        ->orWhereHas('dealer', function ($dealer) use ($search) {
                            $dealer->where('cvr', 'like', "%{$search}%")
                                ->orWhere('city', 'like', "%{$search}%")
                                ->orWhereHas('owner', function ($owner) use ($search) {
                                    $owner->where('name', 'like', "%{$search}%")
                                        ->orWhere('email', 'like', "%{$search}%");
                                });
                        });
                });
            }
        }

        $sortBy = $request->input('sort', 'created_at');
        $allowedSorts = ['created_at', 'last_activity_at', 'first_contacted_at', 'id'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }
        $sortOrder = strtolower((string) $request->input('order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $this->paginated($query->paginate($request->integer('limit', 15)));
    }

    public function show(int $id): JsonResponse
    {
        $lead = Lead::with([
            'vehicle:id,title,registration,slug,dealer_id,price',
            'buyerUser:id,name,email,phone',
            'dealer:id,user_id,cvr,city,slug,address',
            'dealer.owner:id,name,email,phone',
            'assignedUser:id,name,email',
            'leadStage:id,name',
            'leadIntent:id,name',
            'leadCategory:id,name,description',
            'source:id,name',
            'enquiry',
            'stageHistory.changedByUser:id,name',
            'lostReason:id,name',
        ])->findOrFail($id);

        return $this->success($lead);
    }
}
