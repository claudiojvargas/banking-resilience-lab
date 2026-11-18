<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $examples = [
            ['A', 'Ana Exemplo', 'ana@example.test', 100000],
            ['B', 'Bruno Exemplo', 'bruno@example.test', 50000],
        ];

        foreach ($examples as [$identifier, $name, $email, $balance]) {
            $user = User::firstOrCreate(['email' => $email], ['name' => $name]);
            Account::firstOrCreate(['identifier' => $identifier], [
                'user_id' => $user->id,
                'balance_cents' => $balance,
            ]);
        }
    }
}
