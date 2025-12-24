<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Services\ContactService;
use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Helpers\FilterHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    public function __construct(
        private ContactService $contactService
    ) {}

    /**
     * Get contacts list
     */
    public function getContacts(Request $request): JsonResponse
    {
        $query = Contact::query();

        // Apply search
        $search = $request->input('search');
        $searchableFields = [
            'name', 'phone', 'email', 'company_name', 'contact_person', 'address', 'remarks'
        ];
        FilterHelper::applySearch($query, $search, $searchableFields);

        // Apply filters
        $filters = json_decode($request->input('filters', '[]'), true);
        $joinOperator = $request->input('joinOperator', 'or');
        FilterHelper::applyFilters($query, $filters, $joinOperator);

        // Apply sorting
        $sort = json_decode($request->input('sort', '[]'), true);
        FilterHelper::applySorting($query, $sort);

        // Paginate
        $perPage = $request->input('perPage', 10);
        $contacts = $query->withCount(['purchases', 'sales'])->paginate($perPage);

        return $this->paginated($contacts);
    }

    /**
     * Get contact by serial number
     */
    public function getContactBySerial(int $serialNo): JsonResponse
    {
        $contact = Contact::where('serial_no', $serialNo)
            ->withCount(['purchases', 'sales'])
            ->firstOrFail();

        return $this->success($contact);
    }

    /**
     * Create contact
     */
    public function store(StoreContactRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Handle file uploads
        if ($request->hasFile('images')) {
            $data['images'] = $request->file('images');
        }

        $contact = $this->contactService->createContact($data);

        return $this->created($contact);
    }

    /**
     * Update contact
     */
    public function update(UpdateContactRequest $request, Contact $contact): JsonResponse
    {
        $data = $request->validated();

        // Handle file uploads
        if ($request->hasFile('images')) {
            $data['images'] = $request->file('images');
        }

        $contact = $this->contactService->updateContact($contact, $data);

        return $this->success($contact);
    }

    /**
     * Delete contact
     */
    public function destroy(Contact $contact): JsonResponse
    {
        $this->contactService->deleteContact($contact);

        return $this->noContent();
    }

}

