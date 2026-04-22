<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\District;
use App\Models\Region;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class BulkCustomerUploadChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private array $chunkData,
        private int $userId,
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

                if (
                    empty($rowData['name']) ||
                    empty($rowData['bank_name']) ||
                    empty($rowData['bank_account']) ||
                    empty($rowData['phone1']) ||
                    empty($rowData['sex']) ||
                    empty($rowData['region_id']) ||
                    empty($rowData['district_id'])
                ) {
                    throw new \Exception("Row {$rowNumber}: Missing required fields");
                }

                if (!in_array(strtoupper((string) $rowData['sex']), ['M', 'F'], true)) {
                    throw new \Exception("Row {$rowNumber}: Sex must be M or F");
                }

                $regionId = null;
                $districtId = null;

                if (!empty($rowData['region_id'])) {
                    if (is_numeric($rowData['region_id'])) {
                        $regionId = (int) $rowData['region_id'];
                    } else {
                        $region = Region::where('name', trim((string) $rowData['region_id']))->first();
                        $regionId = $region ? (int) $region->id : null;
                    }
                }

                if (!empty($rowData['district_id'])) {
                    if (is_numeric($rowData['district_id'])) {
                        $districtId = (int) $rowData['district_id'];
                    } else {
                        $district = District::where('name', trim((string) $rowData['district_id']))->first();
                        $districtId = $district ? (int) $district->id : null;
                    }
                }

                $phone1 = $this->formatPhoneNumber(trim((string) $rowData['phone1']));
                $phone2 = !empty($rowData['phone2']) ? $this->formatPhoneNumber(trim((string) $rowData['phone2'])) : null;

                $category = 'Borrower';

                $bankName = strtoupper(trim((string) $rowData['bank_name']));
                $bankAccount = trim((string) $rowData['bank_account']);

                $alreadyExists = Customer::query()
                    ->where('bank_name', $bankName)
                    ->where('bank_account', $bankAccount)
                    ->exists();

                if ($alreadyExists) {
                    DB::rollBack();
                    $skipped++;
                    $this->updateProgress($success, $failed, $skipped, 1);
                    continue;
                }

                $customerData = [
                    'name' => trim((string) $rowData['name']),
                    'bank_name' => $bankName,
                    'bank_account' => $bankAccount,
                    'phone1' => $phone1,
                    'phone2' => $phone2,
                    'dob' => !empty($rowData['dob']) ? $rowData['dob'] : null,
                    'sex' => strtoupper((string) $rowData['sex']),
                    'region_id' => $regionId,
                    'district_id' => $districtId,
                    'work' => trim((string) ($rowData['work'] ?? '')),
                    'workAddress' => trim((string) ($rowData['workaddress'] ?? $rowData['workAddress'] ?? '')),
                    'idType' => trim((string) ($rowData['idtype'] ?? $rowData['idType'] ?? '')),
                    'idNumber' => trim((string) ($rowData['idnumber'] ?? $rowData['idNumber'] ?? '')),
                    'relation' => trim((string) ($rowData['relation'] ?? '')),
                    'description' => trim((string) ($rowData['description'] ?? '')),
                    'customerNo' => 100000 + (Customer::max('id') ?? 0) + 1,
                    'password' => Hash::make('1234567890'),
                    'branch_id' => $this->branchId,
                    'company_id' => $this->companyId,
                    'registrar' => $this->userId,
                    'dateRegistered' => now()->toDateString(),
                    'category' => $category,
                ];

                $customer = Customer::create($customerData);

                $existingMembership = DB::table('group_members')->where('customer_id', $customer->id)->first();
                if (!$existingMembership) {
                    DB::table('group_members')->insert([
                        'group_id' => 1,
                        'customer_id' => $customer->id,
                        'status' => 'active',
                        'joined_date' => now()->toDateString(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::commit();
                $success++;
                $this->updateProgress($success, $failed, $skipped, 1);
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('Bulk customer upload row failed', [
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

