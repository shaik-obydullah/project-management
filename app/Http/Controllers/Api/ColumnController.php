<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Column;
use App\Models\Project;
use App\Models\Issue;
use Illuminate\Http\Request;

class ColumnController extends Controller
{
    public function board(Request $request, Project $project)
    {
        abort_unless($project->isMember($request->user()), 403, 'You are not a member of this project.');

        $columns = $project->columns()
            ->with('issues', fn ($q) => $q
                ->with(['assignee:id,name,avatar', 'labels:id,name,color', 'creator:id,name', 'subtasks:id,parent_id'])
                ->orderBy('position'))
            ->get();

        // Assign issues with no column (new issues) to the first column
        $backlog = Issue::where('project_id', $project->id)
            ->whereNull('column_id')
            ->with(['assignee:id,name,avatar', 'labels:id,name,color', 'creator:id,name', 'subtasks:id,parent_id'])
            ->orderBy('position')
            ->get();

        return response()->json([
            'columns' => $columns,
            'backlog' => $backlog,
        ]);
    }

    public function store(Request $request, Project $project)
    {
        abort_unless($project->isAdmin($request->user()), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'],
            'wip_limit' => ['nullable', 'integer', 'min:1'],
        ]);

        $column = Column::create([
            'project_id' => $project->id,
            'name' => $validated['name'],
            'color' => $validated['color'] ?? null,
            'wip_limit' => $validated['wip_limit'] ?? null,
        ]);

        return response()->json([
            'message' => 'Column created',
            'column' => $column,
        ], 201);
    }

    public function update(Request $request, Column $column)
    {
        abort_unless($column->project->isAdmin($request->user()), 403);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'],
            'wip_limit' => ['nullable', 'integer', 'min:1'],
        ]);

        $column->update($validated);

        return response()->json([
            'message' => 'Column updated',
            'column' => $column,
        ]);
    }

    public function destroy(Request $request, Column $column)
    {
        abort_unless($column->project->isAdmin($request->user()), 403);

        // Move issues to null column (backlog) before deleting
        $column->issues()->update(['column_id' => null]);

        $column->delete();

        return response()->json(['message' => 'Column deleted']);
    }

    public function reorder(Request $request, Project $project)
    {
        abort_unless($project->isAdmin($request->user()), 403);

        $validated = $request->validate([
            'columns' => ['required', 'array'],
            'columns.*.id' => ['required', 'exists:columns,id'],
            'columns.*.position' => ['required', 'integer'],
        ]);

        foreach ($validated['columns'] as $item) {
            Column::where('id', $item['id'])
                ->where('project_id', $project->id)
                ->update(['position' => $item['position']]);
        }

        return response()->json(['message' => 'Columns reordered']);
    }
}