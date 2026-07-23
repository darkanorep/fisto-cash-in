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
        $this->validateEntries($accountTitles);

        return DB::transaction(function () use ($transaction, $accountTitles, $module) {
            $transaction->voucherAccountEntries()->delete();

            return collect($accountTitles)->map(
                fn (array $accountTitle) => $transaction->voucherAccountEntries()->create(
                    $this->mapEntryFields($accountTitle, $module)
                )
            );
        });
    }

    public function appendEntries(Transaction $transaction, array $accountTitles, string $module): Collection
    {
        $this->validateEntries($accountTitles);

        return DB::transaction(fn () => collect($accountTitles)->map(
            fn (array $accountTitle) => $transaction->voucherAccountEntries()->create(
                $this->mapEntryFields($accountTitle, $module)
            )
        ));
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
                        "Account Title entry at index {$index} is missing required field '{$required}'."
                    );
                }
            }
        }
    }
}
