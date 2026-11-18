<?php

namespace App\Http\Controllers;

use App\Models\Transfer;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransferController
{
    public function store(Request $request, TransferService $service): JsonResponse
    {
        $transfer = $service->execute($request->only(['source_account_id', 'destination_account_id', 'amount_cents']));

        return response()->json(['data' => $transfer->fresh()], 201);
    }

    public function show(Transfer $transfer): JsonResponse
    {
        return response()->json(['data' => $transfer]);
    }
}
