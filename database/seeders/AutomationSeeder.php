<?php

namespace Database\Seeders;

use App\Models\Automation;
use Illuminate\Database\Seeder;

class AutomationSeeder extends Seeder
{
    /**
     * The renewal automation described in the operations brief. Created
     * disabled -- an operator turns it on once the customer list is loaded.
     */
    public function run(): void
    {
        Automation::updateOrCreate(
            ['name' => 'Daily Health Insurance Renewal Calls'],
            [
                'enabled'                => false,
                'frequency'              => 'daily',
                'run_at'                 => '08:00',
                'timezone'               => 'Asia/Kolkata',
                'expiry_within_days'     => 30,
                'customer_statuses'      => ['pending'],
                'skip_do_not_call'       => true,
                'skip_already_renewed'   => true,
                'skip_active_callback'   => true,
                'min_days_between_calls' => 1,
                'max_calls_per_run'      => 200,
                'max_retries'            => 2,
                'window_start'           => '09:00',
                'window_end'             => '19:00',
                'attempts_per_second'    => 1.0,
            ],
        );
    }
}
