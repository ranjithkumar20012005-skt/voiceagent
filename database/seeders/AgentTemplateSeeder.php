<?php

namespace Database\Seeders;

use App\Models\AgentTemplate;
use Illuminate\Database\Seeder;

/**
 * The master agent catalogue customers pick from.
 *
 * Seeded WITHOUT provider identifiers on purpose. The voice platform publishes
 * no agent-authoring API, so each master agent has to be built and published in
 * the platform dashboard once, by an administrator, and its id and version
 * recorded here afterwards:
 *
 *     php artisan voice:template-bind lead-qualification --agent-id=... --agent-version=1
 *
 * Until that is done a template reports itself unprovisioned, and creating an
 * agent against it fails with a clear message instead of producing an agent that
 * looks ready and cannot call. That is the deliberate choice: no faked success.
 */
class AgentTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $languages = ['English', 'Hindi', 'Telugu', 'Tamil', 'Kannada', 'Malayalam', 'Marathi'];

        $templates = [
            [
                'slug'        => 'lead-qualification',
                'name'        => 'Lead Qualification',
                'category'    => 'Sales',
                'description' => 'Calls a new enquiry, checks how serious they are, and books the next step.',
                'modes'       => ['instant_leads', 'bulk_campaigns'],
                'variables'   => ['business_name' => '', 'agent_name' => '', 'goal' => ''],
                'order'       => 1,
            ],
            [
                'slug'        => 'appointment-booking',
                'name'        => 'Appointment Booking',
                'category'    => 'Scheduling',
                'description' => 'Offers available slots, confirms one, and repeats it back to the customer.',
                'modes'       => ['instant_leads', 'bulk_campaigns', 'inbound'],
                'variables'   => ['business_name' => '', 'agent_name' => '', 'available_slots' => ''],
                'order'       => 2,
            ],
            [
                'slug'        => 'insurance-renewal',
                'name'        => 'Insurance Renewal',
                'category'    => 'Insurance',
                'description' => 'Reminds a customer their policy is expiring, explains the renewal, and schedules a callback.',
                'modes'       => ['bulk_campaigns', 'instant_leads'],
                'variables'   => ['business_name' => '', 'policy_number' => '', 'renewal_premium' => ''],
                'order'       => 3,
            ],
            [
                'slug'        => 'recruiting-screen',
                'name'        => 'Recruiting Screen',
                'category'    => 'Hiring',
                'description' => 'Runs a first-round screen against a role and records the answers.',
                'modes'       => ['instant_leads', 'bulk_campaigns'],
                'variables'   => ['business_name' => '', 'role_title' => ''],
                'order'       => 4,
            ],
            [
                'slug'        => 'customer-support',
                'name'        => 'Customer Support',
                'category'    => 'Support',
                'description' => 'Answers common questions from your own material and hands over when it cannot.',
                'modes'       => ['inbound', 'instant_leads'],
                'variables'   => ['business_name' => '', 'support_hours' => ''],
                'order'       => 5,
            ],
        ];

        foreach ($templates as $t) {
            AgentTemplate::updateOrCreate(
                ['slug' => $t['slug']],
                [
                    'name'                    => $t['name'],
                    'category'                => $t['category'],
                    'description'             => $t['description'],
                    'provider'                => config('voice.provider', 'sarvam'),
                    'supported_languages'     => $languages,
                    'supported_calling_modes' => $t['modes'],
                    'default_variables'       => $t['variables'],
                    'sort_order'              => $t['order'],
                    // Left active so the catalogue is browsable; isProvisioned()
                    // is what gates actually using one.
                    'status'                  => 'active',
                ],
            );
        }
    }
}
