<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Label;
use App\Models\Project;
use Illuminate\Http\Request;

class LabelController extends Controller
{
    public function index(Request $request, Project $project)
    {
        abort_unless($project->isMember($request->user()), 403, 'You are not a member of this project.');

        return response()->json(['labels' => $project->labels()->orderBy('name')->get()]);
    }

    public function store(Request $request, Project $project)
    {
        abort_unless($project->isMember($request->user()), 403, 'You are not a member of this project.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'color' => ['sometimes', 'string', 'max:20'],
        ]);

        $label = Label::create([
            'project_id' => $project->id,
            'name' => $validated['name'],
            'color' => $validated['color'] ?? '#64748b',
        ]);

        return response()->json([
            'message' => 'Label created',
            'label' => $label,
        ], 201);
    }

    public function update(Request $request, Label $label)
    {
        abort_unless($label->project->isMember($request->user()), 403, 'You are not a member of this project.');

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:50'],
            'color' => ['sometimes', 'string', 'max:20'],
        ]);

        $label->update($validated);

        return response()->json(['message' => 'Label updated', 'label' => $label]);
    }

    public function destroy(Request $request, Label $label)
    {
        abort_unless($label->project->isMember($request->user()), 403, 'You are not a member of this project.');

        $label->delete();

        return response()->json(['message' => 'Label deleted']);
    }
}