<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrganizationController extends Controller
{
    public function index(Request $request)
    {
        $organizations = $request->user()->organizations()
            ->with(['owner:id,name,email', 'projects:id,organization_id,name,key,color'])
            ->get();

        return response()->json(['organizations' => $organizations]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $organization = Organization::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']).'-'.Str::random(4),
            'owner_id' => $request->user()->id,
        ]);

        $organization->members()->attach($request->user()->id, ['role' => 'owner']);

        $organization->load('owner:id,name,email');

        return response()->json([
            'message' => 'Organization created',
            'organization' => $organization,
        ], 201);
    }

    public function show(Request $request, Organization $organization)
    {
        $this->authorizeAccess($request->user(), $organization);

        $organization->load(['members', 'projects']);

        return response()->json(['organization' => $organization]);
    }

    public function update(Request $request, Organization $organization)
    {
        $this->authorizeOwner($request->user(), $organization);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'settings' => ['sometimes', 'array'],
        ]);

        $organization->update($validated);

        return response()->json([
            'message' => 'Organization updated',
            'organization' => $organization,
        ]);
    }

    public function destroy(Request $request, Organization $organization)
    {
        $this->authorizeOwner($request->user(), $organization);

        $organization->delete();

        return response()->json(['message' => 'Organization deleted']);
    }

    public function addMember(Request $request, Organization $organization)
    {
        $this->authorizeOwner($request->user(), $organization);

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['sometimes', 'in:owner,admin,member'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $organization->members()->syncWithoutDetaching([
            $user->id => ['role' => $validated['role'] ?? 'member'],
        ]);

        return response()->json(['message' => 'Member added to organization']);
    }

    public function removeMember(Request $request, Organization $organization, User $user)
    {
        $this->authorizeOwner($request->user(), $organization);

        $organization->members()->detach($user->id);

        return response()->json(['message' => 'Member removed from organization']);
    }

    private function authorizeAccess(User $user, Organization $organization): void
    {
        abort_unless($organization->isMember($user), 403, 'You are not a member of this organization.');
    }

    private function authorizeOwner(User $user, Organization $organization): void
    {
        abort_unless($organization->isOwner($user), 403, 'Only the organization owner can perform this action.');
    }
}