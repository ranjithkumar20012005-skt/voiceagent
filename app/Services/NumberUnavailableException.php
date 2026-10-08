<?php

namespace App\Services;

use RuntimeException;

/**
 * A number could not be claimed or attached.
 *
 * The message is already customer-safe wording -- it names no provider and
 * leaks no reason beyond "unavailable" -- so it can be surfaced directly.
 */
class NumberUnavailableException extends RuntimeException
{
}
