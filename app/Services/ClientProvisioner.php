<?php

namespace App\Services;

use App\Contracts\VoiceAgentProviderInterface;
use App\Models\Agent;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use App\Support\Tenancy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Onboarding a client, from our side.
 *
 * Clients do not sign themselves up in this model: our team creates the
 * workspace, creates the client's login, and maps the workspace to the hosted
 * agent we built for them. Each step is a transaction, because a workspace with
 * no owner cannot be logged into and an orphaned user cannot reach a dashboard.
 */
class ClientProvisioner
{
    public function __construct(
        private readonly VoiceAgentProviderInterface $provider,
        private readonly Tenancy $tenancy,
    ) {
    }

    /**
     * Create a client workspace and its first (owner) user.
     *
     * @param  array{business_name:string,contact_name:string,email:string,password?:string|null,default_language?:string|null,timezone?:string|null}  $data
     * @return array{workspace:Workspace,user:User,generated_password:?string}
     */
    public function createClient(array $data): array
    {
        // A generated password is returned once, for the admin to hand over. It
        // is never stored in readable form or written to the log.
        $generated = blank($data['password'] ?? null) ? Str::password(14) : null;
        $password  = $generated ?? $data['password'];

        return DB::transaction(function () use ($data, $password, $generated) {
            $workspace = Workspace::create([
                'name'             => $data['business_name'],
                'slug'             => Workspace::uniqueSlug($data['business_name']),
                'business_name'    => $data['business_name'],
                'default_language' => $data['default_language'] ?? null,
                'timezone'         => $data['timezone'] ?? config('app.timezone', 'Asia/Kolkata'),
                'currency'         => config('voice.billing.currency', 'INR'),
                'status'           => 'active',
            ]);

            $user = User::create([
                'name'     => $data['contact_name'],
                'email'    => $data['email'],
                'password' => Hash::make($password),
            ]);

            Membership::create([
                'workspace_id' => $workspace->id,
                'user_id'      => $user->id,
                'role'         => Membership::OWNER,
            ]);

            $user->forceFill(['current_workspace_id' => $workspace->id])->save();

            Log::info('admin.client.created', [
                'workspace_id' => $workspace->id,
                'action'       => 'create_client',
            ]);

            return ['workspace' => $workspace->refresh(), 'user' => $user->refresh(), 'generated_password' => $generated];
        });
    }

    /**
     * Add another login to an existing client workspace.
     *
     * @return array{user:User,generated_password:?string}
     */
    public function addClientUser(Workspace $workspace, string $name, string $email, ?string $password = null, string $role = Membership::MEMBER): array
    {
        $generated = blank($password) ? Str::password(14) : null;
        $secret    = $generated ?? $password;

        return DB::transaction(function () use ($workspace, $name, $email, $secret, $role, $generated) {
            $user = User::create([
                'name'     => $name,
                'email'    => $email,
                'password' => Hash::make($secret),
            ]);

            Membership::create([
                'workspace_id' => $workspace->id,
                'user_id'      => $user->id,
                'role'         => $role,
            ]);

            $user->forceFill(['current_workspace_id' => $workspace->id])->save();

            return ['user' => $user->refresh(), 'generated_password' => $generated];
        });
    }

    /**
     * Map a workspace to the hosted agent our team built for it.
     *
     * The provider identifiers are written here and nowhere else in the request
     * cycle, and they are hidden on the model, so they cannot reach a client view.
     *
     * @param  array{display_name:string,description?:string|null,provider_agent_id:string,provider_agent_version:int,provider_deployment_id?:string|null,default_language?:string|null,calling_mode?:string|null}  $data
     */
    public function mapAgent(Workspace $workspace, array $data, ?Agent $agent = null): Agent
    {
        // Run as the workspace being administered. Without this the global scope
        // would still be the admin's own workspace, and refresh()/relations on
        // the client's agent would come back empty.
        return $this->tenancy->actingAs($workspace, fn () => $this->performMapping($workspace, $data, $agent));
    }

    private function performMapping(Workspace $workspace, array $data, ?Agent $agent): Agent
    {
        $agent = DB::transaction(function () use ($workspace, $data, $agent) {
            $attributes = [
                'workspace_id'     => $workspace->id,
                'name'             => $data['display_name'],
                'description'      => $data['description'] ?? null,
                'default_language' => $data['default_language'] ?? $workspace->default_language,
                'calling_mode'     => $data['calling_mode'] ?? Agent::MODE_INSTANT_LEADS,
            ];

            if ($agent) {
                $agent->fill($attributes)->save();
            } else {
                // Created outside the workspace scope: an administrator is acting
                // for a tenant they are not a member of.
                $agent = new Agent($attributes);
                $agent->workspace_id = $workspace->id;
                $agent->save();
            }

            $agent->forceFill([
                'provider'               => config('voice.provider', 'sarvam'),
                'provider_agent_id'      => $data['provider_agent_id'],
                'provider_agent_version' => $data['provider_agent_version'],
                'provider_deployment_id' => $data['provider_deployment_id'] ?? $agent->provider_deployment_id,
            ])->save();

            return $agent;
        });

        // Resolve the binding through the provider so a bad mapping fails here,
        // with the reason recorded internally, rather than at call time.
        try {
            $resolved = $this->provider->createAgent($agent->refresh());

            $agent->forceFill([
                'provider_metadata' => $resolved['provider_metadata'],
                'status'            => Agent::READY,
                'error_message'     => null,
                'last_synced_at'    => now(),
            ])->save();
        } catch (\Throwable $e) {
            $agent->forceFill([
                'status'        => Agent::ERROR,
                'error_message' => $e->getMessage(),
            ])->save();

            Log::warning('admin.agent.mapping_failed', [
                'workspace_id' => $workspace->id,
                'agent_id'     => $agent->id,
                'action'       => 'map_agent',
                'message'      => $e->getMessage(),
            ]);
        }

        if (! $agent->is_default) {
            $agent->markAsDefault();
        }

        return $agent->refresh();
    }
}
