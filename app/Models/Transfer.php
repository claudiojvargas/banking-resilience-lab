<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    use HasUuids;

    protected $fillable = ['source_account_id', 'destination_account_id', 'amount_cents', 'status'];

    protected function casts(): array
    {
        return ['amount_cents' => 'integer'];
    }
}
