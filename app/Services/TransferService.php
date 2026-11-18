<?php

namespace App\Services;

use App\Exceptions\DebitInterrupted;
use App\Models\Account;
use App\Models\Transfer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;

class TransferService
{
    public const MAX_AMOUNT_CENTS = 1_000_000;

    public function execute(array $input, bool $failAfterDebit = false): Transfer
    {
        if ($failAfterDebit && (! app()->environment('local', 'testing') || ! config('lab.failures_enabled'))) {
            throw new LogicException('A injeção de falhas exige APP_ENV local ou testing e LAB_FAILURES_ENABLED=true.');
        }

        $validated = Validator::make($input, [
            'source_account_id' => ['bail', 'required', 'integer:strict', 'exists:accounts,id'],
            'destination_account_id' => ['bail', 'required', 'integer:strict', 'different:source_account_id', 'exists:accounts,id'],
            'amount_cents' => ['bail', 'required', 'integer:strict', 'min:1', 'max:'.self::MAX_AMOUNT_CENTS],
        ])->validate();

        $source = Account::findOrFail($validated['source_account_id']);
        $destination = Account::findOrFail($validated['destination_account_id']);
        $amount = $validated['amount_cents'];

        if ($source->balance_cents < $amount) {
            throw ValidationException::withMessages(['amount_cents' => 'Saldo insuficiente na conta de origem.']);
        }

        // A sequência fica intencionalmente sem transação, locks ou compensação para o exercício.
        $transfer = Transfer::create([
            'source_account_id' => $source->id,
            'destination_account_id' => $destination->id,
            'amount_cents' => $amount,
            'status' => 'pending',
        ]);

        $context = ['transfer_uuid' => $transfer->id, 'run_id' => (string) Str::uuid()];
        Log::info('Transferência', $context + ['stage' => 'pending']);

        $source->balance_cents -= $amount;
        $source->save();
        Log::info('Transferência', $context + ['stage' => 'after_debit']);

        if ($failAfterDebit) {
            Log::warning('Transferência interrompida', $context + ['stage' => 'after_debit']);
            throw new DebitInterrupted($transfer->id, $context['run_id']);
        }

        $destination->balance_cents += $amount;
        $destination->save();
        Log::info('Transferência', $context + ['stage' => 'after_credit']);

        $transfer->update(['status' => 'completed']);
        Log::info('Transferência', $context + ['stage' => 'completed']);

        return $transfer;
    }
}
