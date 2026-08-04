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
    protected array $splittableAmountFields = ['amount'];

    /**
     * Sync voucher entries for a transaction, optionally scaling monetary
     * fields down to represent this transaction's proportional share of a
     * combined multi-transaction deposit.
     *
     * @param  array       $accountTitles   The full (unsplit) DR/CR lines entered once in the UI.
     * @param  string      $module          e.g. 'deposit', 'tag', etc.
     * @param  float|null  $ratio           This transaction's share (0.0–1.0) of the batch total.
     *                                      Pass null to persist accountTitles unscaled (single-
     *                                      transaction case, or non-deposit statuses).
     * @param  bool        $isLastInBatch   True for the last transaction in the batch — it absorbs
     *                                      the rounding remainder so debits/credits still balance
     *                                      exactly to the original entered amounts.
     * @param  array       $runningTotals   Reference array tracking how much of each account line
     *                                      has already been allocated to previous transactions in
     *                                      this batch. Pass the SAME array by reference across all
     *                                      calls in a batch loop.
     */
    public function syncEntries(
        Transaction $transaction,
        array $accountTitles,
        string $module,
        ?float $ratio = null,
        bool $isLastInBatch = false,
        array &$runningTotals = []
    ): Collection {
        if (!$transaction->exists) {
            throw new \InvalidArgumentException('Cannot sync voucher entries: transaction is not persisted.');
        }

        $this->validateEntries($accountTitles);

        $entriesToPersist = $ratio === null
            ? $accountTitles
            : $this->scaleAccountTitles($accountTitles, $ratio, $isLastInBatch, $runningTotals);

        return DB::transaction(function () use ($transaction, $entriesToPersist, $module) {
            $transaction->voucherAccountEntries()
                ->where('module', $module)
                ->delete();

            return collect($entriesToPersist)->map(
                fn (array $accountTitle) => $transaction->voucherAccountEntries()->create(
                    $this->mapEntryFields($accountTitle, $module)
                )
            );
        });
    }

    protected function scaleAccountTitles(
        array $accountTitles,
        float $ratio,
        bool $isLastInBatch,
        array &$runningTotals
    ): array {
        foreach ($accountTitles as $lineIndex => &$line) {
            // Stable key per line so running totals track the SAME line
            // across different transactions in the batch. `code` uniquely
            // identifies an account title/DR-CR line in this schema.
            $lineKey = $line['code'] ?? $lineIndex;

            foreach ($this->splittableAmountFields as $field) {
                if (!array_key_exists($field, $line) || !is_numeric($line[$field])) {
                    continue;
                }

                $original       = (float) $line[$field];
                $allocatedSoFar = $runningTotals[$lineKey][$field] ?? 0.0;

                $scaled = $isLastInBatch
                    ? round($original - $allocatedSoFar, 2)   // absorb remainder
                    : round($original * $ratio, 2);           // normal proportional split

                $runningTotals[$lineKey][$field] = $allocatedSoFar + $scaled;
                $line[$field] = $scaled;
            }
        }

        return $accountTitles;
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
                    throw new \InvalidArgumentException(
                        "Voucher entry at index {$index} is missing required field '{$required}'."
                    );
                }
            }
        }
    }
}
