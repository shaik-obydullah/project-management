<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Issue;
use App\Models\Project;
use App\Models\Sprint;
use Illuminate\Http\Request;

class SprintController extends Controller
{
    public function index(Request $request, Project $project)
    {
        abort_unless($project->isMember($request->user()), 403, 'You are not a member of this project.');

        $sprints = $project->sprints()
            ->withCount('issues')
            ->with(['issues' => fn ($q) => $q
                ->select('issues.id', 'issues.key', 'issues.title', 'issues.column_id', 'issues.sprint_id')
                ->with('column:id,name')])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['sprints' => $sprints]);
    }

    public function store(Request $request, Project $project)
    {
        abort_unless($project->isMember($request->user()), 403, 'You are not a member of this project.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'goal' => ['nullable', 'string'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $sprint = Sprint::create([
            'project_id' => $project->id,
            'name' => $validated['name'],
            'goal' => $validated['goal'] ?? null,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
        ]);

        return response()->json([
            'message' => 'Sprint created',
            'sprint' => $sprint,
        ], 201);
    }

    public function update(Request $request, Sprint $sprint)
    {
        abort_unless($sprint->project->isMember($request->user()), 403, 'You are not a member of this project.');

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'goal' => ['nullable', 'string'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
        ]);

        $sprint->update($validated);

        return response()->json([
            'message' => 'Sprint updated',
            'sprint' => $sprint,
        ]);
    }

    public function start(Request $request, Sprint $sprint)
    {
        abort_unless($sprint->project->isMember($request->user()), 403, 'You are not a member of this project.');

        $sprint->update([
            'status' => 'active',
            'starts_at' => $sprint->starts_at ?? now()->toDateString(),
        ]);

        return response()->json(['message' => 'Sprint started', 'sprint' => $sprint]);
    }

    public function complete(Request $request, Sprint $sprint)
    {
        abort_unless($sprint->project->isMember($request->user()), 403, 'You are not a member of this project.');

        $sprint->update([
            'status' => 'completed',
            'ends_at' => $sprint->ends_at ?? now()->toDateString(),
        ]);

        return response()->json(['message' => 'Sprint completed', 'sprint' => $sprint]);
    }

    public function assignIssues(Request $request, Sprint $sprint)
    {
        abort_unless($sprint->project->isMember($request->user()), 403, 'You are not a member of this project.');

        $validated = $request->validate([
            'issue_ids' => ['required', 'array'],
            'issue_ids.*' => ['integer', 'exists:issues,id'],
        ]);

        foreach ($validated['issue_ids'] as $issueId) {
            Issue::where('id', $issueId)
                ->where('project_id', $sprint->project_id)
                ->update(['sprint_id' => $sprint->id]);
        }

        return response()->json(['message' => 'Issues assigned to sprint']);
    }

    public function destroy(Request $request, Sprint $sprint)
    {
        abort_unless($sprint->project->isMember($request->user()), 403, 'You are not a member of this project.');

        $sprint->issues()->update(['sprint_id' => null]);
        $sprint->delete();

        return response()->json(['message' => 'Sprint deleted']);
    }
}