<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\Vehicle;
use App\Models\Contact;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Models\Notification;
use App\Services\FinancialAccountService;
use App\Services\TransactionService;
use App\Services\FileService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(
        private FinancialAccountService $financialAccountService,
        private TransactionService $transactionService,
        private FileService $fileService,
        private NotificationService $notificationService
    ) {}

    /**
     * Validate vehicle and contact exist
     */
    public function validateVehicleAndContact(int $vehicleId, int $contactId): array
    {
        $vehicle = Vehicle::findOrFail($vehicleId);
        $contact = Contact::findOrFail($contactId);

        return ['vehicle' => $vehicle, 'contact' => $contact];
    }

    /**
     * Create transaction entries for purchase
     */
    public function createTransactionEntries(
        int $vehicleInventoryAccountId,
        int $paidFromAccountId,
        int $accountsPayableAccountId,
        float $purchasePrice,
        float $paidAmount
    ): array {
        $isPendingPayment = $paidAmount < $purchasePrice;
        $entries = [];

        // Debit: Vehicle Inventory Account
        $entries[] = [
            'financial_account_id' => $vehicleInventoryAccountId,
            'amount' => $purchasePrice,
            'type' => 'debit',
        ];

        // Credit: Paid From Account
        $entries[] = [
            'financial_account_id' => $paidFromAccountId,
            'amount' => $paidAmount,
            'type' => 'credit',
        ];

        // Credit: Accounts Payable (if pending payment)
        if ($isPendingPayment) {
            $entries[] = [
                'financial_account_id' => $accountsPayableAccountId,
                'amount' => $purchasePrice - $paidAmount,
                'type' => 'credit',
            ];
        }

        return $entries;
    }

    /**
     * Create a purchase
     */
    public function createPurchase(array $purchaseData): Purchase
    {
        return DB::transaction(function () use ($purchaseData) {
            // Validate entities
            $entities = $this->validateVehicleAndContact(
                $purchaseData['vehicle_id'],
                $purchaseData['contact_id']
            );
            $vehicle = $entities['vehicle'];
            $contact = $entities['contact'];

            // Update vehicle status
            $vehicle->update([
                'status' => 'Available',
                'pending_works' => array_merge($vehicle->pending_works ?? [], ['Documents pending']),
            ]);

            // Get or create financial accounts
            $vehicleInventoryAccount = $this->financialAccountService->getOrCreateVehicleInventoryAccount();
            $accountsPayableAccount = $this->financialAccountService->getOrCreateAccountsPayableAccount();

            // Validate paid from account
            $paidFromAccount = $this->financialAccountService->validateAccount(
                $purchaseData['paid_from_financial_account_id']
            );

            // Create transaction entries
            $transactionEntries = $this->createTransactionEntries(
                $vehicleInventoryAccount->id,
                $paidFromAccount->id,
                $accountsPayableAccount->id,
                $purchaseData['purchase_price'],
                $purchaseData['paid_amount']
            );

            // Create transaction
            $narration = "Purchase of {$vehicle->make} {$vehicle->model}";
            if ($contact->name || $contact->company_name) {
                $narration .= " from " . ($contact->name ?? $contact->company_name);
            }

            $transaction = $this->transactionService->createTransaction([
                'type' => 'Vehicle Purchase',
                'date' => $purchaseData['purchase_date'],
                'narration' => $narration,
                'remarks' => null,
                'images' => [],
            ], $transactionEntries);

            // Create purchase
            $purchase = Purchase::create([
                'purchase_date' => $purchaseData['purchase_date'],
                'purchase_type' => $purchaseData['purchase_type'],
                'payment_mode' => $purchaseData['payment_mode'],
                'images' => $purchaseData['images'] ?? [],
                'vehicle_id' => $vehicle->id,
                'contact_id' => $contact->id,
                'paid_from_financial_account_id' => $paidFromAccount->id,
                'transaction_id' => $transaction->id,
            ]);

            // Create purchase notifications
            $this->notificationService->createPurchaseNotifications(
                $purchase,
                $vehicle,
                $contact
            );

            return $purchase->load(['vehicle', 'contact', 'paidFromFinancialAccount', 'transaction.entries.financialAccount']);
        });
    }

    /**
     * Update a purchase
     */
    public function updatePurchase(Purchase $purchase, array $purchaseData): Purchase
    {
        return DB::transaction(function () use ($purchase, $purchaseData) {
            // Validate entities
            $entities = $this->validateVehicleAndContact(
                $purchaseData['vehicle_id'],
                $purchaseData['contact_id']
            );
            $vehicle = $entities['vehicle'];
            $contact = $entities['contact'];

            // Get or create financial accounts
            $vehicleInventoryAccount = $this->financialAccountService->getOrCreateVehicleInventoryAccount();
            $accountsPayableAccount = $this->financialAccountService->getOrCreateAccountsPayableAccount();

            // Validate paid from account
            $paidFromAccount = $this->financialAccountService->validateAccount(
                $purchaseData['paid_from_financial_account_id']
            );

            // Create transaction entries
            $transactionEntries = $this->createTransactionEntries(
                $vehicleInventoryAccount->id,
                $paidFromAccount->id,
                $accountsPayableAccount->id,
                $purchaseData['purchase_price'],
                $purchaseData['paid_amount']
            );

            // Update transaction
            $narration = "Purchase of {$vehicle->make} {$vehicle->model} from " . ($contact->name ?? $contact->company_name);

            $this->transactionService->updateTransaction(
                $purchase->transaction,
                [
                    'date' => $purchaseData['purchase_date'],
                    'narration' => $narration,
                ],
                $transactionEntries
            );

            // Delete old images if new ones are provided
            if (isset($purchaseData['images']) && is_array($purchaseData['images'])) {
                $oldImages = $purchase->images ?? [];
                if (!empty($oldImages)) {
                    $this->fileService->deleteFiles($oldImages);
                }
            }

            // Update purchase
            $purchase->update([
                'purchase_date' => $purchaseData['purchase_date'],
                'purchase_type' => $purchaseData['purchase_type'],
                'payment_mode' => $purchaseData['payment_mode'],
                'images' => $purchaseData['images'] ?? [],
                'vehicle_id' => $vehicle->id,
                'contact_id' => $contact->id,
                'paid_from_financial_account_id' => $paidFromAccount->id,
            ]);

            // Clear and recreate purchase notifications
            $this->notificationService->createPurchaseNotifications(
                $purchase->fresh(),
                $vehicle,
                $contact,
                true // clear existing notifications
            );

            return $purchase->load(['vehicle', 'contact', 'paidFromFinancialAccount', 'transaction.entries.financialAccount']);
        });
    }

    /**
     * Delete a purchase
     */
    public function deletePurchase(Purchase $purchase): void
    {
        DB::transaction(function () use ($purchase) {
            // Delete related notifications
            Notification::whereJsonContains('metadata->purchase_id', $purchase->id)
                ->whereJsonContains('metadata->vehicle_id', $purchase->vehicle_id)
                ->delete();

            // Delete transaction and purchase images
            $transactionImages = $purchase->transaction->images ?? [];
            $purchaseImages = $purchase->images ?? [];
            
            if (!empty($transactionImages)) {
                $this->fileService->deleteFiles($transactionImages);
            }
            if (!empty($purchaseImages)) {
                $this->fileService->deleteFiles($purchaseImages);
            }

            // Delete transaction (cascades to entries)
            $this->transactionService->deleteTransaction($purchase->transaction);

            // Delete purchase
            $purchase->delete();
        });
    }
}

