<?php

namespace App\Services;

use App\Models\Transaction;
use http\Exception\InvalidArgumentException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VoucherEntryService
{
    private const FILLABLE_FIELDS = [
        'code', 'title', 'account_type', 'account_group', 'sub_group',
        'financial_statement', 'normal_balance', 'unit',
        'company_code', 'company_name',
        'business_unit_code', 'business_unit_name',
        'department_code', 'department_name',
        'unit_code', 'unit_name',
        'sub_unit_code', 'sub_unit_name',
        'location_code', 'location_name',
        'amount', 'charge_name'
    ];

    public function syncEntries(Transaction $transaction, array $accountTitles, string $module): Collection
    {
        if (!$transaction->exists) {
            throw new InvalidArgumentException('Cannot sync voucher entries: transaction is not persisted.');
        }

        $this->validateEntries($accountTitles);

        return DB::transaction(function () use ($transaction, $accountTitles, $module) {
            $transaction->voucherAccountEntries()
                ->where('module', $module)
                ->delete();

            return collect($accountTitles)->map(
                fn (array $accountTitle) => $transaction->voucherAccountEntries()->create(
                    $this->mapEntryFields($accountTitle, $module)
                )
            );
        });
    }

    public function appendEntries(Transaction $transaction, array $accountTitles, string $module): Collection
    {
        if (!$transaction->exists) {
            throw new InvalidArgumentException('Cannot append voucher entries: transaction is not persisted.');
        }

        $this->validateEntries($accountTitles);

        return DB::transaction(fn () => collect($accountTitles)->map(
            fn (array $accountTitle) => $transaction->voucherAccountEntries()->create(
                $this->mapEntryFields($accountTitle, $module)
            )
        ));
    }

    public function getEntriesForModule(Transaction $transaction, string $module): Collection
    {
        return $transaction->voucherAccountEntries()
            ->where('module', $module)
            ->get();
    }

    public function deleteEntriesForModule(Transaction $transaction, string $module): int
    {
        return $transaction->voucherAccountEntries()
            ->where('module', $module)
            ->delete();
    }

    private function mapEntryFields(array $accountTitle, string $module): array
    {
        $row = ['module' => $module];

        foreach (self::FILLABLE_FIELDS as $field) {
            $row[$field] = $accountTitle[$field] ?? null;
        }

        return $row;
    }

    private function validateEntries(array $accountTitles): void
    {
        foreach ($accountTitles as $index => $accountTitle) {
            foreach (['code', 'title', 'normal_balance', 'amount'] as $required) {
                if (!array_key_exists($required, $accountTitle) || $accountTitle[$required] === null) {
                    throw new InvalidArgumentException(
                        "Voucher entry at index {$index} is missing required field '{$required}'."
                    );
                }
            }
        }
    }
}
