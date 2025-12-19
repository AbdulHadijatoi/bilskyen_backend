<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Vehicle;
use App\Models\Contact;
use App\Models\Purchase;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Models\Notification;
use App\Services\FinancialAccountService;
use App\Services\TransactionService;
use App\Services\FileService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

class SaleService
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
     * Get vehicle cost price from latest purchase
     */
    public function getVehicleCostPrice(int $vehicleId): float
    {
        $latestPurchase = Purchase::where('vehicle_id', $vehicleId)
            ->with('transaction.entries')
            ->orderBy('purchase_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$latestPurchase || !$latestPurchase->transaction) {
            return 0;
        }

        // Calculate purchase price from transaction entries (debit to Vehicle Inventory)
        return $latestPurchase->transaction->entries()
            ->where('type', 'debit')
            ->whereHas('financialAccount', function ($query) {
                $query->where('name', 'Vehicle Inventory');
            })
            ->sum('amount');
    }

    /**
     * Create transaction entries for sale
     */
    public function createTransactionEntries(
        int $vehicleInventoryAccountId,
        int $receivedToAccountId,
        int $accountsReceivableAccountId,
        int $salesRevenueAccountId,
        int $costOfGoodsSoldAccountId,
        float $salePrice,
        float $receivedAmount,
        float $costPrice
    ): array {
        $isPendingPayment = $receivedAmount < $salePrice;
        $entries = [];

        // Revenue recognition entries
        if ($isPendingPayment) {
            // Debit: Cash/Bank (received amount)
            $entries[] = [
                'financial_account_id' => $receivedToAccountId,
                'amount' => $receivedAmount,
                'type' => 'debit',
            ];

            // Debit: Accounts Receivable (outstanding amount)
            $entries[] = [
                'financial_account_id' => $accountsReceivableAccountId,
                'amount' => $salePrice - $receivedAmount,
                'type' => 'debit',
            ];
        } else {
            // Debit: Cash/Bank (full amount)
            $entries[] = [
                'financial_account_id' => $receivedToAccountId,
                'amount' => $salePrice,
                'type' => 'debit',
            ];
        }

        // Credit: Sales Revenue
        $entries[] = [
            'financial_account_id' => $salesRevenueAccountId,
            'amount' => $salePrice,
            'type' => 'credit',
        ];

        // Cost of goods sold entries (only if cost price is available)
        if ($costPrice > 0) {
            // Debit: Cost of Goods Sold
            $entries[] = [
                'financial_account_id' => $costOfGoodsSoldAccountId,
                'amount' => $costPrice,
                'type' => 'debit',
            ];

            // Credit: Vehicle Inventory
            $entries[] = [
                'financial_account_id' => $vehicleInventoryAccountId,
                'amount' => $costPrice,
                'type' => 'credit',
            ];
        }

        return $entries;
    }

    /**
     * Create a sale
     */
    public function createSale(array $saleData): Sale
    {
        return DB::transaction(function () use ($saleData) {
            // Validate entities
            $entities = $this->validateVehicleAndContact(
                $saleData['vehicle_id'],
                $saleData['contact_id']
            );
            $vehicle = $entities['vehicle'];
            $contact = $entities['contact'];

            // Update vehicle status
            $vehicle->update(['status' => 'Sold']);

            // Get or create financial accounts
            $vehicleInventoryAccount = $this->financialAccountService->getOrCreateVehicleInventoryAccount();
            $accountsReceivableAccount = $this->financialAccountService->getOrCreateAccountsReceivableAccount();
            $salesRevenueAccount = $this->financialAccountService->getOrCreateSalesRevenueAccount();
            $costOfGoodsSoldAccount = $this->financialAccountService->getOrCreateCostOfGoodsSoldAccount();

            // Validate received to account
            $receivedToAccount = $this->financialAccountService->validateAccount(
                $saleData['received_to_financial_account_id']
            );

            // Get vehicle cost price
            $costPrice = $this->getVehicleCostPrice($vehicle->id);

            // Create transaction entries
            $transactionEntries = $this->createTransactionEntries(
                $vehicleInventoryAccount->id,
                $receivedToAccount->id,
                $accountsReceivableAccount->id,
                $salesRevenueAccount->id,
                $costOfGoodsSoldAccount->id,
                $saleData['sale_price'],
                $saleData['received_amount'],
                $costPrice
            );

            // Create transaction
            $narration = "Sale of {$vehicle->make} {$vehicle->model} to " . ($contact->name ?? $contact->company_name);

            $transaction = $this->transactionService->createTransaction([
                'type' => 'Vehicle Sale',
                'date' => $saleData['sale_date'],
                'narration' => $narration,
                'remarks' => null,
                'images' => [],
            ], $transactionEntries);

            // Create sale
            $sale = Sale::create([
                'sale_date' => $saleData['sale_date'],
                'sale_type' => $saleData['sale_type'],
                'payment_mode' => $saleData['payment_mode'],
                'images' => $saleData['images'] ?? [],
                'vehicle_id' => $vehicle->id,
                'contact_id' => $contact->id,
                'received_to_financial_account_id' => $receivedToAccount->id,
                'transaction_id' => $transaction->id,
            ]);

            // Create sale notifications
            $this->notificationService->createSaleNotifications(
                $sale,
                $vehicle,
                $contact
            );

            return $sale->load(['vehicle', 'contact', 'receivedToFinancialAccount', 'transaction.entries.financialAccount']);
        });
    }

    /**
     * Update a sale
     */
    public function updateSale(Sale $sale, array $saleData): Sale
    {
        return DB::transaction(function () use ($sale, $saleData) {
            // Validate entities
            $entities = $this->validateVehicleAndContact(
                $saleData['vehicle_id'],
                $saleData['contact_id']
            );
            $vehicle = $entities['vehicle'];
            $contact = $entities['contact'];

            // Update vehicle status
            $vehicle->update([
                'status' => 'Sold',
                'pending_works' => array_merge($vehicle->pending_works ?? [], ['Documents pending']),
            ]);

            // Get or create financial accounts
            $vehicleInventoryAccount = $this->financialAccountService->getOrCreateVehicleInventoryAccount();
            $accountsReceivableAccount = $this->financialAccountService->getOrCreateAccountsReceivableAccount();
            $salesRevenueAccount = $this->financialAccountService->getOrCreateSalesRevenueAccount();
            $costOfGoodsSoldAccount = $this->financialAccountService->getOrCreateCostOfGoodsSoldAccount();

            // Validate received to account
            $receivedToAccount = $this->financialAccountService->validateAccount(
                $saleData['received_to_financial_account_id']
            );

            // Get vehicle cost price
            $costPrice = $this->getVehicleCostPrice($vehicle->id);

            // Create transaction entries
            $transactionEntries = $this->createTransactionEntries(
                $vehicleInventoryAccount->id,
                $receivedToAccount->id,
                $accountsReceivableAccount->id,
                $salesRevenueAccount->id,
                $costOfGoodsSoldAccount->id,
                $saleData['sale_price'],
                $saleData['received_amount'],
                $costPrice
            );

            // Update transaction
            $narration = "Sale of {$vehicle->make} {$vehicle->model} to " . ($contact->name ?? $contact->company_name);

            $this->transactionService->updateTransaction(
                $sale->transaction,
                [
                    'date' => $saleData['sale_date'],
                    'narration' => $narration,
                ],
                $transactionEntries
            );

            // Delete old images if new ones are provided
            if (isset($saleData['images']) && is_array($saleData['images'])) {
                $oldImages = $sale->images ?? [];
                if (!empty($oldImages)) {
                    $this->fileService->deleteFiles($oldImages);
                }
            }

            // Update sale
            $sale->update([
                'sale_date' => $saleData['sale_date'],
                'sale_type' => $saleData['sale_type'],
                'payment_mode' => $saleData['payment_mode'],
                'images' => $saleData['images'] ?? [],
                'vehicle_id' => $vehicle->id,
                'contact_id' => $contact->id,
                'received_to_financial_account_id' => $receivedToAccount->id,
            ]);

            // Clear and recreate sale notifications
            $this->notificationService->createSaleNotifications(
                $sale->fresh(),
                $vehicle,
                $contact,
                true // clear existing notifications
            );

            return $sale->load(['vehicle', 'contact', 'receivedToFinancialAccount', 'transaction.entries.financialAccount']);
        });
    }

    /**
     * Delete a sale
     */
    public function deleteSale(Sale $sale): void
    {
        DB::transaction(function () use ($sale) {
            // Update vehicle status back to Available
            $vehicle = $sale->vehicle;
            if ($vehicle) {
                $vehicle->update(['status' => 'Available']);
            }

            // Delete transaction and sale images
            $transactionImages = $sale->transaction->images ?? [];
            $saleImages = $sale->images ?? [];
            
            if (!empty($transactionImages)) {
                $this->fileService->deleteFiles($transactionImages);
            }
            if (!empty($saleImages)) {
                $this->fileService->deleteFiles($saleImages);
            }

            // Delete transaction (cascades to entries)
            $this->transactionService->deleteTransaction($sale->transaction);

            // Delete sale
            $sale->delete();
        });
    }
}

