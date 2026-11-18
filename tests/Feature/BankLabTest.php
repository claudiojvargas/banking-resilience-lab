<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Transfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BankLabTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function beforeRefreshingDatabase(): void
    {
        $connection = DB::connection();
        $this->assertTrue(app()->environment('testing'));
        $this->assertSame('pgsql', $connection->getDriverName());
        $this->assertSame('db-test', $connection->getConfig('host'));
        $this->assertSame('bank_lab_test', $connection->getDatabaseName());
    }

    public function test_it_lists_and_reads_seeded_accounts(): void
    {
        $this->get('/api/health')->assertOk()->assertExactJson(['status' => 'ok', 'database' => 'ok']);
        $accounts = $this->get('/api/accounts')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.identifier', 'A')
            ->assertJsonPath('data.0.balance_cents', 100000)
            ->assertJsonPath('data.1.identifier', 'B')
            ->assertJsonPath('data.1.balance_cents', 50000);

        $this->get('/api/accounts/'.$accounts->json('data.0.id'))
            ->assertOk()->assertJsonPath('data.balance_cents', 100000);
    }

    public function test_unknown_resources_have_the_same_json_response(): void
    {
        $expected = ['message' => 'Recurso não encontrado.'];

        $this->get('/api/accounts/999999')->assertNotFound()->assertExactJson($expected);
        $this->get('/api/transfers/'.Str::uuid())->assertNotFound()->assertExactJson($expected);
    }

    public function test_it_completes_a_transfer_and_preserves_the_total(): void
    {
        $transfer = $this->postJson('/api/transfers', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.amount_cents', 10000)
            ->assertJsonPath('data.status', 'completed');

        $uuid = $transfer->json('data.id');
        $this->assertTrue(Str::isUuid($uuid));
        $this->assertBalances(90000, 60000);
        $this->assertDatabaseCount('transfers', 1);
        $this->assertSame('completed', Transfer::findOrFail($uuid)->status);

        $this->get('/api/transfers/'.$uuid)
            ->assertOk()
            ->assertJsonPath('data.id', $uuid)
            ->assertJsonPath('data.status', 'completed');
    }

    #[DataProvider('invalidTransfers')]
    public function test_it_rejects_invalid_transfers_without_writing(array $changes, string $field): void
    {
        $payload = $this->payload();
        if (isset($changes['same_account'])) {
            $changes = ['destination_account_id' => $payload['source_account_id']];
        }

        $this->postJson('/api/transfers', array_replace($payload, $changes))
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonValidationErrors($field);

        $this->assertBalances(100000, 50000);
        $this->assertDatabaseCount('transfers', 0);
    }

    public static function invalidTransfers(): array
    {
        return [
            'zero' => [['amount_cents' => 0], 'amount_cents'],
            'negative' => [['amount_cents' => -1], 'amount_cents'],
            'fractional' => [['amount_cents' => 10.5], 'amount_cents'],
            'numeric string' => [['amount_cents' => '10000'], 'amount_cents'],
            'above maximum' => [['amount_cents' => 1000001], 'amount_cents'],
            'same account' => [['same_account' => true], 'destination_account_id'],
            'unknown source' => [['source_account_id' => 999999], 'source_account_id'],
            'unknown destination' => [['destination_account_id' => 999999], 'destination_account_id'],
            'insufficient balance' => [['amount_cents' => 100001], 'amount_cents'],
        ];
    }

    private function payload(): array
    {
        return [
            'source_account_id' => Account::where('identifier', 'A')->value('id'),
            'destination_account_id' => Account::where('identifier', 'B')->value('id'),
            'amount_cents' => 10000,
        ];
    }

    private function assertBalances(int $source, int $destination): void
    {
        $this->assertSame([$source, $destination], Account::orderBy('identifier')->pluck('balance_cents')->all());
        $this->assertSame(150000, (int) Account::sum('balance_cents'));
    }
}
