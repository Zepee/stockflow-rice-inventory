<?php

namespace App\Services;

use App\Models\TransactionNumberSequence;
use Brick\Math\BigInteger;
use Illuminate\Support\Facades\DB;

class TransactionNumberGenerator
{
    /**
     * Allocate and commit a number before the business transaction starts.
     * A later failure therefore leaves an allowed gap and never reuses a number.
     */
    public function allocate(string $type, string $prefix): string
    {
        $confirmationTime = now(config('app.timezone'));
        $date = $confirmationTime->toDateString();

        return DB::transaction(function () use ($type, $prefix, $date, $confirmationTime): string {
            DB::table('transaction_number_sequences')->insertOrIgnore([
                'transaction_type' => $type,
                'sequence_date' => $date,
                'last_number' => 0,
            ]);

            $sequence = TransactionNumberSequence::query()
                ->where('transaction_type', $type)
                ->where('sequence_date', $date)
                ->lockForUpdate()
                ->firstOrFail();

            $next = BigInteger::of((string) $sequence->last_number)->plus(1);
            $sequence->update(['last_number' => (string) $next]);

            return sprintf('%s-%s-%s', $prefix, $confirmationTime->format('Ymd'), str_pad((string) $next, 4, '0', STR_PAD_LEFT));
        });
    }
}
