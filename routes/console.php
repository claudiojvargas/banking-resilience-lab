<?php

use App\Exceptions\DebitInterrupted;
use App\Models\Account;
use App\Models\Transfer;
use App\Services\TransferService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Command\Command;

$usesExperimentsDatabase = static function (): bool {
    $connection = DB::connection();

    return $connection->getDriverName() === 'pgsql'
        && $connection->getConfig('host') === 'db'
        && $connection->getDatabaseName() === 'bank_lab';
};

Artisan::command('lab:scenario {name}', function (TransferService $transfers) use ($usesExperimentsDatabase): int {
    if ($this->argument('name') !== 'debit-without-credit') {
        $this->error('Cenário desconhecido.');

        return Command::INVALID;
    }

    if (! app()->environment('local', 'testing') || ! config('lab.failures_enabled') || ! $usesExperimentsDatabase()) {
        $this->error('Use o PostgreSQL de experiências (db/bank_lab), APP_ENV local ou testing e LAB_FAILURES_ENABLED=true.');

        return Command::FAILURE;
    }

    $accounts = Account::whereIn('identifier', ['A', 'B'])->pluck('id', 'identifier');

    if (! isset($accounts['A'], $accounts['B'])) {
        $this->error('Execute o seed das contas A e B.');

        return Command::FAILURE;
    }

    try {
        $transfers->execute([
            'source_account_id' => $accounts['A'],
            'destination_account_id' => $accounts['B'],
            'amount_cents' => 10000,
        ], failAfterDebit: true);
    } catch (DebitInterrupted $failure) {
        $this->error($failure->getMessage());
        $this->line("UUID: {$failure->transferId}");
        $this->line("Execução: {$failure->runId}");

        return Command::FAILURE;
    }

    $this->error('O cenário terminou sem acionar a interrupção esperada.');

    return Command::FAILURE;
})->purpose('Reproduz uma interrupção controlada no banco local de experiências.');

Artisan::command('lab:snapshot', function (): void {
    $accounts = Account::orderBy('identifier')->get(['identifier', 'balance_cents']);

    $this->table(['Conta', 'Saldo (centavos)'], $accounts->toArray());
    $this->line('Total (centavos): '.$accounts->sum('balance_cents'));

    $transfers = Transfer::orderBy('created_at')->orderBy('id')
        ->get(['id', 'source_account_id', 'destination_account_id', 'amount_cents', 'status']);

    $this->table(['UUID', 'Origem', 'Destino', 'Valor (centavos)', 'Estado'], $transfers->toArray());
})->purpose('Inspeciona contas e transferências persistidas.');

Artisan::command('lab:reset-experiments {--confirm : Confirma a exclusão de todas as tabelas de db/bank_lab}', function () use ($usesExperimentsDatabase): int {
    if (! app()->environment('local') || ! $usesExperimentsDatabase() || ! $this->option('confirm')) {
        $this->error('Reset restrito a APP_ENV=local e db/bank_lab. Passe --confirm para apagar as tabelas.');

        return Command::FAILURE;
    }

    return $this->call('migrate:fresh', ['--seed' => true]);
})->purpose('Recria somente o banco descartável de experiências (db/bank_lab).');
