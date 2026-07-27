<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionSyncService
{
    private const SETTING_KEY_CASH = 'external_cash_transaction';
    private const SETTING_KEY_NON_CASH = 'external_non_cash_transaction';

    /**
     * Resolve the user_id to stamp on a synced (Aranca-originated) transaction,
     * based on whether its mode_of_payment is classified as cash or non-cash.
     *
     * Reads directly from the `settings` table (no ORM, no cache) — the table
     * is written to elsewhere via raw DB::table() queries, so caching here
     * would risk silently serving a stale user_id after a manual settings edit.
     *
     * Returns null (and logs) if mode_of_payment is missing, unmapped, or the
     * corresponding setting row has no value1 configured — callers must decide
     * how to handle that (reject the request, fall back, or allow null).
     */
    public function resolveSyncUserId(?string $modeOfPayment): ?int
    {
        if (blank($modeOfPayment)) {
            logger()->warning('Aranca sync: missing mode_of_payment, cannot resolve user_id.');
            return null;
        }

        $normalized = Str::lower(trim($modeOfPayment));

        $settingKey = match (true) {
            in_array($normalized, Transaction::cashPaymentOptions, true) => self::SETTING_KEY_CASH,
            in_array($normalized, Transaction::nonCashPaymentOptions, true) => self::SETTING_KEY_NON_CASH,
            default => null,
        };

        if ($settingKey === null) {
            logger()->warning('Aranca sync: unmapped mode_of_payment, cannot resolve user_id.', [
                'mode_of_payment' => $modeOfPayment,
            ]);
            return null;
        }

        $userId = DB::table('settings')->where('key', $settingKey)->value('value1');

        if ($userId === null) {
            logger()->error("Aranca sync: setting '{$settingKey}' has no assign user configured.");
        }

        return $userId !== null ? (int) $userId : null;
    }

    /**
     * Whether the given request payload represents an Aranca-originated sync transaction.
     */
    public function isSyncTransaction(?string $syncId, ?int $syncPaymentRecordId): bool
    {
        return filled($syncId) || filled($syncPaymentRecordId);
    }
}
