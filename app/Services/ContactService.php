<?php

namespace App\Services;

use App\Models\Contact;
use App\Services\FileService;
use Illuminate\Support\Facades\DB;

class ContactService
{
    public function __construct(
        private FileService $fileService
    ) {}

    /**
     * Create a contact
     */
    public function createContact(array $contactData): Contact
    {
        return DB::transaction(function () use ($contactData) {
            // Handle file uploads if present
            if (isset($contactData['images']) && is_array($contactData['images'])) {
                $uploadedFiles = [];
                foreach ($contactData['images'] as $file) {
                    if (is_string($file)) {
                        // Already a URL
                        $uploadedFiles[] = $file;
                    } else {
                        // Upload file
                        $this->fileService->validateFile($file);
                        $uploadedFiles[] = $this->fileService->uploadFiles([$file], 'public', 'contacts')[0];
                    }
                }
                $contactData['images'] = $uploadedFiles;
            }

            return Contact::create($contactData);
        });
    }

    /**
     * Update a contact
     */
    public function updateContact(Contact $contact, array $contactData): Contact
    {
        return DB::transaction(function () use ($contact, $contactData) {
            // Delete old images if new ones are provided
            if (isset($contactData['images']) && is_array($contactData['images'])) {
                $oldImages = $contact->images ?? [];
                if (!empty($oldImages)) {
                    $this->fileService->deleteFiles($oldImages);
                }

                // Handle new file uploads
                $uploadedFiles = [];
                foreach ($contactData['images'] as $file) {
                    if (is_string($file)) {
                        // Already a URL
                        $uploadedFiles[] = $file;
                    } else {
                        // Upload file
                        $this->fileService->validateFile($file);
                        $uploadedFiles[] = $this->fileService->uploadFiles([$file], 'public', 'contacts')[0];
                    }
                }
                $contactData['images'] = $uploadedFiles;
            }

            // Update contact
            $contact->update($contactData);

            return $contact->fresh();
        });
    }

    /**
     * Delete a contact
     */
    public function deleteContact(Contact $contact): void
    {
        DB::transaction(function () use ($contact) {
            // Delete contact images
            $images = $contact->images ?? [];
            if (!empty($images)) {
                $this->fileService->deleteFiles($images);
            }

            // Delete contact
            $contact->delete();
        });
    }
}


