<?php

namespace App\Contracts;

interface ClamScanner
{
    public function enabled(): bool;

    /**
     * Scan a payload and throw a RuntimeException when it is infected or the
     * scanner is unavailable (callers fail closed).
     */
    public function scanOrFail(string $buffer): void;
}
