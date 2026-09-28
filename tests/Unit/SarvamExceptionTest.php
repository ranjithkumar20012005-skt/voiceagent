<?php

namespace Tests\Unit;

use App\Services\SarvamException;
use Tests\TestCase;

/**
 * Each upstream status must produce its own actionable message and an honest
 * local HTTP status. Collapsing everything into "502 / authentication failed"
 * is exactly the bug these tests exist to prevent.
 */
class SarvamExceptionTest extends TestCase
{
    public static function statusExpectations(): array
    {
        return [
            //  upstream, expected message fragment,                       local status
            '401 bad key'      => [401, 'Invalid or missing Sarvam API key.',            500],
            '403 no access'    => [403, 'does not have access to this organisation',     500],
            '402 no credit'    => [402, 'insufficient credits',                            402],
            '404 missing'      => [404, 'Configured Sarvam resource was not found.',     500],
            '422 bad payload'  => [422, 'Voice agent configuration is invalid',          422],
            '429 rate limited' => [429, 'rate limit or usage limit reached',             429],
            '500 upstream'     => [500, 'temporarily unavailable',                       502],
            '503 upstream'     => [503, 'temporarily unavailable',                       502],
            'unreachable'      => [null, 'Could not reach the voice service',            502],
        ];
    }

    /** @param int|null $upstream */
    #[\PHPUnit\Framework\Attributes\DataProvider('statusExpectations')]
    public function test_each_status_maps_to_its_own_message_and_local_status(
        ?int $upstream,
        string $fragment,
        int $expectedLocal,
    ): void {
        $e = new SarvamException('raw upstream text', $upstream);

        $this->assertStringContainsString($fragment, $e->userMessage());
        $this->assertSame($expectedLocal, $e->responseStatus());
    }

    public function test_distinct_statuses_do_not_share_a_message(): void
    {
        $messages = [];

        foreach ([401, 402, 403, 404, 422, 429, 500] as $status) {
            $messages[] = (new SarvamException('x', $status))->userMessage();
        }

        $this->assertSame(
            count($messages),
            count(array_unique($messages)),
            'Every upstream status must have a distinct message.'
        );
    }

    public function test_a_non_auth_failure_is_never_labelled_an_auth_failure(): void
    {
        foreach ([402, 404, 422, 429, 500, 503] as $status) {
            $message = (new SarvamException('x', $status))->userMessage();

            $this->assertStringNotContainsStringIgnoringCase('api key', $message);
            $this->assertStringNotContainsStringIgnoringCase('authentication', $message);
        }
    }

    public function test_the_raw_upstream_detail_is_preserved_for_logs(): void
    {
        $e = new SarvamException('(401) Unauthorized -- Invalid API key format.', 401);

        $this->assertSame('(401) Unauthorized -- Invalid API key format.', $e->upstreamDetail());
        // ...but the operator-facing message stays clean.
        $this->assertSame('Invalid or missing Sarvam API key.', $e->userMessage());
    }
}
