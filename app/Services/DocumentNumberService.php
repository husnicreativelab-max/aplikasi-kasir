<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use LogicException;

class DocumentNumberService
{
    public function next(string $prefix): string
    {
        if (DB::transactionLevel() < 1) {
            throw new LogicException('Nomor dokumen harus dibuat di dalam DB transaction.');
        }

        $today = Carbon::now(config('app.timezone'));
        $date = $today->toDateString();
        $now = $today->toDateTimeString();

        // Create the daily counter if needed, then lock it so simultaneous checkouts
        // cannot receive the same number.
        DB::table('document_sequences')->insertOrIgnore([
            'sequence_type' => $prefix,
            'sequence_date' => $date,
            'last_number' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $sequence = DB::table('document_sequences')
            ->where('sequence_type', $prefix)
            ->where('sequence_date', $date)
            ->lockForUpdate()
            ->first();

        $number = (int) $sequence->last_number + 1;

        DB::table('document_sequences')
            ->where('id', $sequence->id)
            ->update(['last_number' => $number, 'updated_at' => $now]);

        return sprintf('%s-%s-%04d', $prefix, $today->format('Ymd'), $number);
    }
}
