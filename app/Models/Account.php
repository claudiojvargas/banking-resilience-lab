<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = ['user_id', 'identifier', 'balance_cents'];

    protected function casts(): array
    {
        return ['balance_cents' => 'integer'];
    }
}
