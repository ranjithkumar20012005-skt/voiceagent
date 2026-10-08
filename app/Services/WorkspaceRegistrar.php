<?php

namespace App\Services;

use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Signing a new business up.
 *
 * The user, their workspace and their membership are created together or not at
 * all: a user without a workspace cannot reach the dashboard, since the
 * workspace is what every query is scoped by, so a half-finished signup would
 * leave an account that can log in and immediately be logged out again.
 */
class WorkspaceRegistrar
{
    /**
     * @param  array{name:string,email:string,password:string,business_name?:string|null}  $data
     */
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $businessName = trim((string) ($data['business_name'] ?? '')) ?: $data['name'];

            $workspace = Workspace::create([
                'name'          => $businessName,
                'slug'          => Workspace::uniqueSlug($businessName),
                'business_name' => $businessName,
                'timezone'      => config('app.timezone', 'Asia/Kolkata'),
                'currency'      => config('voice.billing.currency', 'INR'),
                'status'        => 'active',
            ]);

            Membership::create([
                'workspace_id' => $workspace->id,
                'user_id'      => $user->id,
                'role'         => Membership::OWNER,
            ]);

            $user->forceFill(['current_workspace_id' => $workspace->id])->save();

            return $user->refresh();
        });
    }
}
