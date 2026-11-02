<?php

namespace App\Imports;

use App\Models\Branch;
use App\Models\Domain;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class DomainsImport implements ToArray, WithHeadingRow
{
    protected $user;
    protected string $role;
    protected bool $overridePaymentCheck;

    public function __construct($user, string $role, bool $overridePaymentCheck = false)
    {
        $this->user = $user;
        $this->role = $role;
        $this->overridePaymentCheck = $overridePaymentCheck;
    }

    public function array(array $array): array
    {
        return $array;
    }

    /**
     * Process parsed rows (from Excel or JSON payload).
     *
     * @param array $rows
     * @return array
     * @throws ValidationException
     */
    public function processRows(array $rows): array
    {
        $cleanedRows = [];
        foreach ($rows as $index => $row) {
            $excelRowNumber = $index + 2; // Row 1 is headings in Excel
            if (!$this->isRowEmpty($row)) {
                $cleanedRows[] = [
                    'row_number' => $excelRowNumber,
                    'data' => (array)$row,
                ];
            }
        }

        if (empty($cleanedRows)) {
            throw ValidationException::withMessages([
                'file' => ['The uploaded file contains no data rows.'],
            ]);
        }

        // 1. Validation of duplicates within the submitted rows
        $this->validateDuplicates($cleanedRows);

        // 2. Pre-validate row fields and business rules
        $validatedData = $this->preValidateRows($cleanedRows);

        // 3. Process create or update in a database transaction
        $createdCount = 0;
        $updatedCount = 0;
        $processed = [];

        DB::beginTransaction();
        try {
            foreach ($validatedData as $item) {
                if ($item['action'] === 'update') {
                    /** @var Domain $domain */
                    $domain = $item['domain'];
                    $domain->update($item['payload']);
                    $updatedCount++;
                    $processed[] = [
                        'row' => $item['row_number'],
                        'id' => $domain->id,
                        'name' => $domain->name,
                        'action' => 'updated',
                    ];
                } else {
                    $domain = Domain::create($item['payload']);
                    $createdCount++;
                    $processed[] = [
                        'row' => $item['row_number'],
                        'id' => $domain->id,
                        'name' => $domain->name,
                        'action' => 'created',
                    ];
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'total_rows' => count($cleanedRows),
            'created_count' => $createdCount,
            'updated_count' => $updatedCount,
            'processed' => $processed,
        ];
    }

    /**
     * Check if a row is completely empty.
     */
    protected function isRowEmpty(array $row): bool
    {
        foreach ($row as $val) {
            if ($val !== null && trim((string)$val) !== '') {
                return false;
            }
        }
        return true;
    }

    /**
     * Validate duplicate names and IDs within the submitted rows.
     *
     * @param array $rows
     * @throws ValidationException
     */
    protected function validateDuplicates(array $rows): void
    {
        $seenNames = [];
        $seenIds = [];
        $duplicateErrors = [];

        foreach ($rows as $item) {
            $rowNum = $item['row_number'];
            $data = $item['data'];

            $name = $this->getRowValue($data, ['name', 'domain_name', 'domain', 'domainname']);
            $id = $this->getRowValue($data, ['id', 'domain_id', 'domainid']);

            if (!empty($name)) {
                $normalizedName = strtolower(trim($name));
                if (isset($seenNames[$normalizedName])) {
                    $duplicateErrors[] = "Duplicate domain name '{$name}' found in row {$rowNum} (previously seen in row {$seenNames[$normalizedName]}).";
                } else {
                    $seenNames[$normalizedName] = $rowNum;
                }
            }

            if (!empty($id) && is_numeric($id)) {
                $intId = (int)$id;
                if (isset($seenIds[$intId])) {
                    $duplicateErrors[] = "Duplicate domain ID '{$intId}' found in row {$rowNum} (previously seen in row {$seenIds[$intId]}).";
                } else {
                    $seenIds[$intId] = $rowNum;
                }
            }
        }

        if (!empty($duplicateErrors)) {
            throw ValidationException::withMessages([
                'file' => $duplicateErrors,
            ]);
        }
    }

    /**
     * Pre-validate rows and determine whether each row creates or updates.
     *
     * @param array $rows
     * @return array
     * @throws ValidationException
     */
    protected function preValidateRows(array $rows): array
    {
        $errors = [];
        $validatedData = [];

        foreach ($rows as $item) {
            $rowNum = $item['row_number'];
            $data = $item['data'];

            $id = $this->getRowValue($data, ['id', 'domain_id', 'domainid']);
            $name = $this->getRowValue($data, ['name', 'domain_name', 'domain', 'domainname']);

            if (empty($id) && empty($name)) {
                $errors[] = "Row {$rowNum}: Either 'name' (or 'domain_name') or 'id' must be provided.";
                continue;
            }

            // Find if domain exists: check id first, then name
            $existingDomain = null;
            if (!empty($id) && is_numeric($id)) {
                $existingDomain = Domain::find((int)$id);
            }

            if (!$existingDomain && !empty($name)) {
                $existingDomain = Domain::whereRaw('LOWER(name) = ?', [strtolower(trim($name))])->first();
            }

            if ($existingDomain) {
                // UPDATE logic
                if (!$this->canAccessDomain($this->user, $this->role, $existingDomain)) {
                    $errors[] = "Row {$rowNum}: You are not authorized to update domain '{$existingDomain->name}'.";
                    continue;
                }

                $updatePayload = $this->buildUpdatePayload($data, $existingDomain, $rowNum, $errors);

                $validatedData[] = [
                    'row_number' => $rowNum,
                    'action' => 'update',
                    'domain' => $existingDomain,
                    'payload' => $updatePayload,
                ];
            } else {
                // CREATE logic
                if (empty($name)) {
                    $errors[] = "Row {$rowNum}: 'name' is required to create a new domain.";
                    continue;
                }

                $createPayload = $this->buildCreatePayload($data, $name, $rowNum, $errors);

                $validatedData[] = [
                    'row_number' => $rowNum,
                    'action' => 'create',
                    'domain' => null,
                    'payload' => $createPayload,
                ];
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages([
                'file' => $errors,
            ]);
        }

        return $validatedData;
    }

    /**
     * Build update payload from row data.
     */
    protected function buildUpdatePayload(array $data, Domain $domain, int $rowNum, array &$errors): array
    {
        $update = [];

        $companyName = $this->getRowValue($data, ['company_name', 'company', 'companyname', 'client_name', 'client']);
        if ($companyName !== null) {
            $update['company_name'] = $companyName;
        }

        $bookingDateRaw = $this->getRowValue($data, ['booking_date', 'bookingdate', 'booking']);
        if ($bookingDateRaw !== null) {
            $bookingDate = $this->parseDate($bookingDateRaw);
            if ($bookingDate) {
                $update['booking_date'] = $bookingDate;
            } else {
                $errors[] = "Row {$rowNum}: Invalid booking_date format '{$bookingDateRaw}'.";
            }
        }

        $expiryDateRaw = $this->getRowValue($data, ['expiry_date', 'expirydate', 'expiry']);
        if ($expiryDateRaw !== null) {
            $expiryDate = $this->parseDate($expiryDateRaw);
            if ($expiryDate) {
                $update['expiry_date'] = $expiryDate;

                // Payment check before renewal if expiry is extended
                if ($expiryDate > $domain->expiry_date) {
                    $domain->load('payments');
                    $totalPaid = $domain->payments()->sum('amount');
                    $balancePending = $domain->total_amount - $totalPaid;

                    if ($balancePending > 0 && !$this->overridePaymentCheck) {
                        $errors[] = "Row {$rowNum}: Cannot extend expiry date for '{$domain->name}' due to pending balance of " . number_format($balancePending, 2) . ". Pass override_payment_check=true to force.";
                    }
                }
            } else {
                $errors[] = "Row {$rowNum}: Invalid expiry_date format '{$expiryDateRaw}'.";
            }
        }

        $totalAmount = $this->getRowValue($data, ['total_amount', 'amount', 'totalamount', 'price', 'total']);
        if ($totalAmount !== null && is_numeric($totalAmount)) {
            $update['total_amount'] = (float)$totalAmount;
        }

        $manualStatus = $this->getRowValue($data, ['manual_status', 'status', 'manualstatus']);
        if ($manualStatus !== null) {
            $update['manual_status'] = $manualStatus;
        }

        $nextFollowupRaw = $this->getRowValue($data, ['next_followup_at', 'next_followup', 'followup_date', 'followup']);
        if ($nextFollowupRaw !== null) {
            $nextFollowup = $this->parseDateTime($nextFollowupRaw);
            if ($nextFollowup) {
                $update['next_followup_at'] = $nextFollowup;
            } else {
                $errors[] = "Row {$rowNum}: Invalid next_followup_at format '{$nextFollowupRaw}'.";
            }
        }

        $lastContactedRaw = $this->getRowValue($data, ['last_contacted_at', 'last_contacted', 'last_contact']);
        if ($lastContactedRaw !== null) {
            $lastContacted = $this->parseDateTime($lastContactedRaw);
            if ($lastContacted) {
                $update['last_contacted_at'] = $lastContacted;
            } else {
                $errors[] = "Row {$rowNum}: Invalid last_contacted_at format '{$lastContactedRaw}'.";
            }
        }

        $renewalDateRaw = $this->getRowValue($data, ['renewal_date', 'renewaldate']);
        if ($renewalDateRaw !== null) {
            $renewalDate = $this->parseDate($renewalDateRaw);
            if ($renewalDate) {
                $update['renewal_date'] = $renewalDate;
            } else {
                $errors[] = "Row {$rowNum}: Invalid renewal_date format '{$renewalDateRaw}'.";
            }
        }

        $remark = $this->getRowValue($data, ['remark', 'remarks', 'comment', 'comments', 'note', 'notes']);
        if ($remark !== null) {
            $update['remark'] = $remark;
        }

        // Sales Person
        $salesPersonRaw = $this->getRowValue($data, ['sales_person_id', 'sales_person', 'salesperson_id', 'salesperson', 'sales_person_email', 'sales_email', 'email']);
        if ($salesPersonRaw !== null && in_array($this->role, ['admin', 'superadmin', 'manager'])) {
            $salesPersonId = $this->resolveSalesPersonId($salesPersonRaw);
            if ($salesPersonId) {
                $update['sales_person_id'] = $salesPersonId;
            } elseif (!empty($salesPersonRaw)) {
                $errors[] = "Row {$rowNum}: Sales person '{$salesPersonRaw}' not found.";
            }
        }

        // Branch
        $branchRaw = $this->getRowValue($data, ['branch_id', 'branch', 'branchname', 'branch_name']);
        if ($branchRaw !== null && in_array($this->role, ['admin', 'superadmin'])) {
            $branchId = $this->resolveBranchId($branchRaw);
            if ($branchId) {
                $update['branch_id'] = $branchId;
            } elseif (!empty($branchRaw)) {
                $errors[] = "Row {$rowNum}: Branch '{$branchRaw}' not found.";
            }
        }

        return $update;
    }

    /**
     * Build create payload from row data.
     */
    protected function buildCreatePayload(array $data, string $name, int $rowNum, array &$errors): array
    {
        $companyName = $this->getRowValue($data, ['company_name', 'company', 'companyname', 'client_name', 'client']);

        $bookingDateRaw = $this->getRowValue($data, ['booking_date', 'bookingdate', 'booking']);
        $bookingDate = $bookingDateRaw ? $this->parseDate($bookingDateRaw) : now()->toDateString();
        if ($bookingDateRaw && !$bookingDate) {
            $errors[] = "Row {$rowNum}: Invalid booking_date format '{$bookingDateRaw}'.";
        }

        $expiryDateRaw = $this->getRowValue($data, ['expiry_date', 'expirydate', 'expiry']);
        if (empty($expiryDateRaw)) {
            $errors[] = "Row {$rowNum}: 'expiry_date' is required to create new domain '{$name}'.";
            $expiryDate = null;
        } else {
            $expiryDate = $this->parseDate($expiryDateRaw);
            if (!$expiryDate) {
                $errors[] = "Row {$rowNum}: Invalid expiry_date format '{$expiryDateRaw}'.";
            }
        }

        $totalAmount = (float)($this->getRowValue($data, ['total_amount', 'amount', 'totalamount', 'price', 'total']) ?? 0);
        $manualStatus = $this->getRowValue($data, ['manual_status', 'status', 'manualstatus']);

        $nextFollowupRaw = $this->getRowValue($data, ['next_followup_at', 'next_followup', 'followup_date', 'followup']);
        $nextFollowup = $nextFollowupRaw ? $this->parseDateTime($nextFollowupRaw) : null;

        $lastContactedRaw = $this->getRowValue($data, ['last_contacted_at', 'last_contacted', 'last_contact']);
        $lastContacted = $lastContactedRaw ? $this->parseDateTime($lastContactedRaw) : null;

        $renewalDateRaw = $this->getRowValue($data, ['renewal_date', 'renewaldate']);
        $renewalDate = $renewalDateRaw ? $this->parseDate($renewalDateRaw) : null;

        $remark = $this->getRowValue($data, ['remark', 'remarks', 'comment', 'comments', 'note', 'notes']);

        // Resolve sales person
        $salesPersonRaw = $this->getRowValue($data, ['sales_person_id', 'sales_person', 'salesperson_id', 'salesperson', 'sales_person_email', 'sales_email', 'email']);
        $salesPersonId = $salesPersonRaw ? $this->resolveSalesPersonId($salesPersonRaw) : null;
        if ($salesPersonRaw && !$salesPersonId) {
            $errors[] = "Row {$rowNum}: Sales person '{$salesPersonRaw}' not found.";
        }

        // Resolve branch
        $branchRaw = $this->getRowValue($data, ['branch_id', 'branch', 'branchname', 'branch_name']);
        $branchId = $branchRaw ? $this->resolveBranchId($branchRaw) : null;
        if ($branchRaw && !$branchId) {
            $errors[] = "Row {$rowNum}: Branch '{$branchRaw}' not found.";
        }

        $payload = [
            'name' => trim($name),
            'company_name' => $companyName,
            'booking_date' => $bookingDate,
            'expiry_date' => $expiryDate,
            'total_amount' => $totalAmount,
            'manual_status' => $manualStatus,
            'next_followup_at' => $nextFollowup,
            'last_contacted_at' => $lastContacted,
            'renewal_date' => $renewalDate,
            'remark' => $remark,
        ];

        // Assign scoping according to authenticated user role
        if ($this->role === 'employee') {
            $payload['sales_person_id'] = $this->user->id;
            $payload['branch_id'] = $this->user->branch_id;
        } elseif ($this->role === 'manager') {
            $payload['branch_id'] = $this->user->branch_id;
            $payload['sales_person_id'] = $salesPersonId ?? $this->user->id;
        } else {
            $payload['branch_id'] = $branchId ?? $this->user?->branch_id;
            $payload['sales_person_id'] = $salesPersonId ?? $this->user?->id;
        }

        return $payload;
    }

    /**
     * Resolve sales person ID by ID, email, or name.
     */
    protected function resolveSalesPersonId($value): ?int
    {
        if ($value === null || trim((string)$value) === '') {
            return null;
        }

        $str = trim((string)$value);

        if (is_numeric($str)) {
            return User::where('id', (int)$str)->value('id');
        }

        if (str_contains($str, '@')) {
            return User::whereRaw('LOWER(email) = ?', [strtolower($str)])->value('id');
        }

        return User::whereRaw('LOWER(name) = ?', [strtolower($str)])->value('id');
    }

    /**
     * Resolve branch ID by ID or name.
     */
    protected function resolveBranchId($value): ?int
    {
        if ($value === null || trim((string)$value) === '') {
            return null;
        }

        $str = trim((string)$value);

        if (is_numeric($str)) {
            return Branch::where('id', (int)$str)->value('id');
        }

        return Branch::whereRaw('LOWER(name) = ?', [strtolower($str)])->value('id');
    }

    /**
     * Parse date supporting Excel serial number and common date string formats.
     */
    protected function parseDate($value): ?string
    {
        if ($value === null || trim((string)$value) === '') {
            return null;
        }

        $str = trim((string)$value);

        // Check if numeric Excel serial date
        if (is_numeric($str) && (float)$str > 20000 && (float)$str < 80000) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float)$str))->format('Y-m-d');
            } catch (\Throwable $e) {
                // fallback
            }
        }

        $formats = ['Y-m-d', 'd-m-Y', 'd/m/Y', 'Y/m/d', 'm/d/Y', 'm-d-Y'];
        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $str);
                if ($parsed !== false && $parsed->format($format) === $str) {
                    return $parsed->format('Y-m-d');
                }
            } catch (\Throwable $e) {
                // continue
            }
        }

        try {
            return Carbon::parse($str)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Parse datetime supporting Excel serial number and common datetime formats.
     */
    protected function parseDateTime($value): ?string
    {
        if ($value === null || trim((string)$value) === '') {
            return null;
        }

        $str = trim((string)$value);

        if (is_numeric($str) && (float)$str > 20000 && (float)$str < 80000) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float)$str))->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                // fallback
            }
        }

        $formats = [
            'Y-m-d H:i:s', 'd-m-Y H:i:s', 'd/m/Y H:i:s',
            'Y-m-d H:i', 'd-m-Y H:i', 'd/m/Y H:i',
            'Y-m-d', 'd-m-Y', 'd/m/Y',
        ];
        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $str);
                if ($parsed !== false) {
                    return $parsed->format('Y-m-d H:i:s');
                }
            } catch (\Throwable $e) {
                // continue
            }
        }

        try {
            return Carbon::parse($str)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Extract cell value matching possible key variants.
     */
    protected function getRowValue(array $row, array $possibleKeys)
    {
        // First try exact key match
        foreach ($possibleKeys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== null && trim((string)$row[$key]) !== '') {
                return trim((string)$row[$key]);
            }
        }

        // Try normalized key match (lowercase, without spaces/underscores)
        $normalizedRow = [];
        foreach ($row as $k => $v) {
            $normKey = strtolower(str_replace([' ', '_', '-'], '', (string)$k));
            $normalizedRow[$normKey] = $v;
        }

        foreach ($possibleKeys as $key) {
            $normKey = strtolower(str_replace([' ', '_', '-'], '', (string)$key));
            if (array_key_exists($normKey, $normalizedRow) && $normalizedRow[$normKey] !== null && trim((string)$normalizedRow[$normKey]) !== '') {
                return trim((string)$normalizedRow[$normKey]);
            }
        }

        return null;
    }

    /**
     * Check if user can access the domain.
     */
    protected function canAccessDomain($user, string $role, Domain $domain): bool
    {
        if (in_array($role, ['admin', 'superadmin', 'accounts'])) {
            return true;
        }

        if ($role === 'manager') {
            return (int)$domain->branch_id === (int)$user?->branch_id;
        }

        if ($role === 'employee') {
            return (int)$domain->sales_person_id === (int)$user?->id;
        }

        return false;
    }
}
