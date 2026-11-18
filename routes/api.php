<?php

use App\Http\Controllers\TransferController;
use App\Models\Account;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    try {
        DB::selectOne('select 1');
    } catch (QueryException) {
        return response()->json(['status' => 'unavailable', 'database' => 'unavailable'], 503);
    }

    return response()->json(['status' => 'ok', 'database' => 'ok']);
});

Route::get('/accounts', fn () => response()->json(['data' => Account::orderBy('identifier')->get()]));
Route::get('/accounts/{account}', fn (Account $account) => response()->json(['data' => $account]))
    ->whereNumber('account');

Route::post('/transfers', [TransferController::class, 'store']);
Route::get('/transfers/{transfer}', [TransferController::class, 'show'])->whereUuid('transfer');
