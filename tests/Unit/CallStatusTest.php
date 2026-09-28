<?php

namespace Tests\Unit;

use App\Support\CallStatus;
use Tests\TestCase;

class CallStatusTest extends TestCase
{
    public function test_it_maps_known_connectivity_values(): void
    {
        $this->assertSame(CallStatus::CONNECTED, CallStatus::connectivityFrom('connected'));
        $this->assertSame(CallStatus::NO_ANSWER, CallStatus::connectivityFrom('no_answer'));
        $this->assertSame(CallStatus::BUSY, CallStatus::connectivityFrom('BUSY'));
        $this->assertSame(CallStatus::CONN_FAILED, CallStatus::connectivityFrom(' failed '));
    }

    public function test_unknown_connectivity_returns_null_rather_than_guessing(): void
    {
        $this->assertNull(CallStatus::connectivityFrom('something-else'));
        $this->assertNull(CallStatus::connectivityFrom(null));
        $this->assertNull(CallStatus::connectivityFrom(''));
    }

    public function test_it_maps_known_dispositions(): void
    {
        $this->assertSame(CallStatus::INTERESTED, CallStatus::outcomeFrom('interested'));
        $this->assertSame(CallStatus::CALLBACK, CallStatus::outcomeFrom('call back'));
        $this->assertSame(CallStatus::CALLBACK, CallStatus::outcomeFrom('Call-Back'));
        $this->assertSame(CallStatus::ALREADY_RENEWED, CallStatus::outcomeFrom('renewed'));
        $this->assertSame(CallStatus::WRONG_PERSON, CallStatus::outcomeFrom('wrong_number'));
        $this->assertSame(CallStatus::DO_NOT_CALL, CallStatus::outcomeFrom('DNC'));
    }

    public function test_an_unclear_outcome_is_never_negative(): void
    {
        foreach (['', null, 'gibberish', 'maybe?', 'customer hung up'] as $input) {
            $result = CallStatus::outcomeFrom($input);

            $this->assertSame(CallStatus::UNKNOWN, $result);
            $this->assertNotSame(CallStatus::NOT_INTERESTED, $result);
            $this->assertNotSame(CallStatus::DO_NOT_CALL, $result);
        }
    }

    public function test_mappings_are_configurable(): void
    {
        config(['sarvam.disposition_map' => ['ha_interesado' => CallStatus::INTERESTED]]);

        $this->assertSame(CallStatus::INTERESTED, CallStatus::outcomeFrom('ha_interesado'));
        // Previously known values now fall through, proving config drives it.
        $this->assertSame(CallStatus::UNKNOWN, CallStatus::outcomeFrom('interested'));
    }

    public function test_every_label_has_a_badge_class(): void
    {
        foreach (array_keys(CallStatus::outcomeLabels()) as $key) {
            $this->assertStringStartsWith('badge-', CallStatus::outcomeBadge($key));
        }

        foreach (array_keys(CallStatus::connectivityLabels()) as $key) {
            $this->assertStringStartsWith('badge-', CallStatus::connectivityBadge($key));
        }
    }
}
