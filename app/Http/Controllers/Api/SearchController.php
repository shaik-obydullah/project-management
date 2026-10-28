<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'q' => ['required', 'string', 'max:100'],
        ]);

        $query = $request->q;
        $user = $request->user();

        $projectIds = $user->projects()->pluck('projects.id');

        // Search issues across user's projects using simple LIKE (reliable without Meilisearch setup)
        $issues = \App\Models\Issue::whereIn('project_id', $projectIds)
            ->where(function ($q) use ($query) {
                $q->where('title', 'ilike', "%{$query}%")
                    ->orWhere('key', 'ilike', "%{$query}%")
                    ->orWhere('description', 'ilike', "%{$query}%");
            })
            ->with(['project:id,name,key,color', 'assignee:id,name,avatar'])
            ->limit(30)
            ->get();

        $projects = $user->projects()
            ->where('name', 'ilike', "%{$query}%")
            ->get();

        return response()->json([
            'issues' => $issues,
            'projects' => $projects,
        ]);
    }
}