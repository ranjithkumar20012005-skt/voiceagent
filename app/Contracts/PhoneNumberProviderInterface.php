<?php

namespace App\Contracts;

use App\Models\Agent;
use App\Models\PhoneNumber;

/**
 * Numbers on the provider side.
 *
 * `listNumbers` reports only what the provider can actually tell us. Where a
 * provider has no endpoint for enumerating its inventory, the implementation
 * says so rather than guessing, and our own phone_numbers table stays the
 * source of truth.
 */
interface PhoneNumberProviderInterface
{
    /** @return array<int,array{phone_number:string,provider_number_id:?string}> */
    public function listNumbers(): array;

    /** True when the provider confirmed the mapping. */
    public function assignNumber(PhoneNumber $number, Agent $agent): bool;

    public function releaseNumber(PhoneNumber $number): bool;

    /** @return array<string,mixed> */
    public function getNumberStatus(PhoneNumber $number): array;

    /** Whether listNumbers() can return real inventory on this provider. */
    public function supportsInventoryListing(): bool;
}
