<?php

namespace App\Exceptions;

use RuntimeException;

class DebitInterrupted extends RuntimeException
{
    public function __construct(
        public readonly string $transferId,
        public readonly string $runId,
    ) {
        parent::__construct('Interrupção intencional após persistir o débito (after_debit).');
    }
}
