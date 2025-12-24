<?php

namespace App\Services;

use App\Models\Enquiry;
use Illuminate\Support\Facades\DB;

class EnquiryService
{
    /**
     * Create an enquiry
     */
    public function createEnquiry(array $enquiryData): Enquiry
    {
        return DB::transaction(function () use ($enquiryData) {
            return Enquiry::create($enquiryData);
        });
    }

    /**
     * Update an enquiry
     */
    public function updateEnquiry(Enquiry $enquiry, array $enquiryData): Enquiry
    {
        return DB::transaction(function () use ($enquiry, $enquiryData) {
            $enquiry->update($enquiryData);
            return $enquiry->fresh();
        });
    }

    /**
     * Delete an enquiry
     */
    public function deleteEnquiry(Enquiry $enquiry): void
    {
        DB::transaction(function () use ($enquiry) {
            $enquiry->delete();
        });
    }
}


