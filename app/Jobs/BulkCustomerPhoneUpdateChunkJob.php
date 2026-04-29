<?php

namespace App\Jobs;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BulkCustomerPhoneUpdateChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private array $chunkData,
        private int $branchId,
        private int $companyId,
        private string $importId,
        private int $chunkIndex,
        private int $totalChunks,
        private int $headerRowIndex
    ) {}

    public function handle(): void
    {
        $success = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($this->chunkData as $rowIndex => $rowData) {
            $rowNumber = $this->headerRowIndex + 2 + ($this->chunkIndex * count($this->chunkData)) + $rowIndex;

            try {
                DB::beginTransaction();

                $bankName = strtoupper(trim((string) ($rowData['bank_name'] ?? '')));
                $bankAccount = trim((string) ($rowData['bank_account'] ?? ''));
                $phone1Raw = trim((string) ($rowData['phone1'] ?? ''));

                if ($bankName === '' || $bankAccount === '' || $phone1Raw === '') {
                    throw new \Exception("Row {$rowNumber}: Missing required fields (bank_name, bank_account, phone1)");
                }

                $phone1 = $this->formatPhoneNumber($phone1Raw);

                $query = Customer::query()
                    ->where('bank_name', $bankName)
                    ->where('bank_account', $bankAccount);

                // If branch/company columns exist in this environment, scope updates to avoid cross-company collisions.
                if (\Schema::hasColumn('customers', 'company_id')) {
                    $query->where('company_id', $this->companyId);
                }
                if (\Schema::hasColumn('customers', 'branch_id')) {
                    $query->where('branch_id', $this->branchId);
                }

                $matches = $query->get();

                if ($matches->count() === 0) {
                    DB::rollBack();
                    $skipped++;
                    $this->updateProgress($success, $failed, $skipped, 1);
                    continue;
                }

                if ($matches->count() > 1) {
                    throw new \Exception("Row {$rowNumber}: Multiple customers found for bank_name + bank_account");
                }

                $customer = $matches->first();
                $customer->phone1 = $phone1;
                $customer->save();

                DB::commit();
                $success++;
                $this->updateProgress($success, $failed, $skipped, 1);
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('Bulk phone update row failed', [
                    'import_id' => $this->importId,
                    'row_number' => $rowNumber,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
                $this->updateProgress($success, $failed, $skipped, 1);
            }
        }

        $progress = Cache::get($this->importId, []);
        if (($this->chunkIndex + 1) >= $this->totalChunks) {
            $progress['status'] = 'completed';
            $progress['percentage'] = 100;
            Cache::put($this->importId, $progress, 3600);
        }
    }

    private function updateProgress(int $successInc, int $failedInc, int $skippedInc, int $processedInc): void
    {
        $progress = Cache::get($this->importId, [
            'status' => 'processing',
            'current' => 0,
            'total' => 1,
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'percentage' => 0,
        ]);

        $progress['current'] = ($progress['current'] ?? 0) + $processedInc;
        $progress['success'] = ($progress['success'] ?? 0) + $successInc;
        $progress['failed'] = ($progress['failed'] ?? 0) + $failedInc;
        $progress['skipped'] = ($progress['skipped'] ?? 0) + $skippedInc;

        $total = max(1, (int) ($progress['total'] ?? 1));
        $progress['percentage'] = min(99, (int) round((($progress['current'] ?? 0) / $total) * 100));
        $progress['status'] = 'processing';

        Cache::put($this->importId, $progress, 3600);
    }

    private function formatPhoneNumber(string $phoneNumber): string
    {
        if ($phoneNumber === '') {
            return $phoneNumber;
        }

        $phoneNumber = preg_replace("/[^0-9+]/", "", $phoneNumber);

        if (substr($phoneNumber, 0, 1) === "0") {
            return "255" . substr($phoneNumber, 1);
        }

        if (substr($phoneNumber, 0, 4) === "+255") {
            return substr($phoneNumber, 1);
        }

        if (substr($phoneNumber, 0, 3) !== "255") {
            return "255" . $phoneNumber;
        }

        return $phoneNumber;
    }
}

