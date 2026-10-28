<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Column;
use App\Models\Issue;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectController extends Controller
{

    public function index(Request $request, Organization $organization)
    {
        abort_unless($organization->isMember($request->user()), 403);

        $projects = $organization->projects()
            ->withCount('issues')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['projects' => $projects]);
    }

    public function store(Request $request, Organization $organization)
    {
        abort_unless($organization->isMember($request->user()), 403, 'You are not a member of this organization.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'key' => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/'],
            'description' => ['nullable', 'string'],
            'color' => ['sometimes', 'string', 'max:20'],
        ]);

        $project = Project::create([
            'organization_id' => $organization->id,
            'name' => $validated['name'],
            'key' => Str::upper($validated['key']),
            'description' => $validated['description'] ?? null,
            'color' => $validated['color'] ?? '#6366f1',
            'issue_prefix' => Str::upper($validated['key']),
            'issue_counter' => 0,
        ]);

        $project->members()->attach($request->user()->id, ['role' => 'admin']);

        foreach (['To Do', 'In Progress', 'Done'] as $index => $name) {
            Column::create([
                'project_id' => $project->id,
                'name' => $name,
                'position' => $index,
                'color' => ['#e2e8f0', '#93c5fd', '#86efac'][$index],
            ]);
        }

        activity()
            ->causedBy($request->user())
            ->performedOn($project)
            ->event('created')
            ->log('project created');

        return response()->json([
            'message' => 'Project created',
            'project' => $project->load('columns', 'members'),
        ], 201);
    }

    public function show(Request $request, Project $project)
    {
        $this->authorizeAccess($request->user(), $project);

        $project->load([
            'organization:id,name,slug',
            'members:id,name,email,avatar',
            'columns',
            'labels',
            'sprints' => fn ($q) => $q->orderBy('created_at', 'desc'),
            'issues' => fn ($q) => $q->whereNull('parent_id'),
        ]);

        return response()->json(['project' => $project]);
    }

    public function update(Request $request, Project $project)
    {
        $this->authorizeAdmin($request->user(), $project);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'color' => ['sometimes', 'string', 'max:20'],
            'is_archived' => ['sometimes', 'boolean'],
        ]);

        $project->update($validated);

        activity()
            ->causedBy($request->user())
            ->performedOn($project)
            ->event('updated')
            ->log('project updated');

        return response()->json([
            'message' => 'Project updated',
            'project' => $project,
        ]);
    }

    public function destroy(Request $request, Project $project)
    {
        $this->authorizeAdmin($request->user(), $project);

        $project->delete();

        return response()->json(['message' => 'Project deleted']);
    }

    public function addMember(Request $request, Project $project)
    {
        $this->authorizeAdmin($request->user(), $project);

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['sometimes', 'in:admin,member,viewer'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $project->members()->syncWithoutDetaching([
            $user->id => ['role' => $validated['role'] ?? 'member'],
        ]);

        return response()->json(['message' => 'Member added to project']);
    }

    public function removeMember(Request $request, Project $project, User $user)
    {
        $this->authorizeAdmin($request->user(), $project);

        $project->members()->detach($user->id);

        return response()->json(['message' => 'Member removed from project']);
    }

    private function authorizeAccess(User $user, Project $project): void
    {
        abort_unless($project->isMember($user), 403, 'You are not a member of this project.');
    }

    private function authorizeAdmin(User $user, Project $project): void
    {
        abort_unless($project->isAdmin($user), 403, 'Only project admins can perform this action.');
    }
}