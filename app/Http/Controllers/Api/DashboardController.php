<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Issue;
use App\Models\Project;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $myIssues = Issue::where('assigned_to', $user->id)
            ->with(['project:id,name,key,color'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $projects = $user->projects()
            ->withCount('issues')
            ->orderBy('updated_at', 'desc')
            ->limit(6)
            ->get();

        $openCount = Issue::where('assigned_to', $user->id)->where('column_id', '!=', null)->count();
        $doneThisWeek = Issue::where('assigned_to', $user->id)
            ->where('updated_at', '>=', now()->subWeek())
            ->count();

        return response()->json([
            'my_issues' => $myIssues,
            'projects' => $projects,
            'stats' => [
                'my_open_issues' => $openCount,
                'updated_this_week' => $doneThisWeek,
                'total_projects' => $projects->count(),
            ],
        ]);
    }

    public function projectStats(Request $request, Project $project)
    {
        abort_unless($project->isMember($request->user()), 403, 'You are not a member of this project.');

        $issues = Issue::where('project_id', $project->id)->with('column:id,name')->get();

        $byType = $issues->groupBy('type')->map->count();
        $byPriority = $issues->groupBy('priority')->map->count();
        $byColumn = Issue::where('project_id', $project->id)
            ->selectRaw('column_id, count(*) as total')
            ->groupBy('column_id')
            ->with('column:id,name')
            ->get()
            ->mapWithKeys(fn ($i) => [$i->column?->name ?? 'Backlog' => $i->total]);

        $recentIssues = Issue::where('project_id', $project->id)
            ->with(['assignee:id,name,avatar'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $completedIssues = $issues->filter(fn (Issue $i) => $i->column?->name
            && in_array($i->column->name, ['Done', 'Completed', 'Closed', 'Complete']));

        return response()->json([
            'total_issues' => $issues->count(),
            'open_issues' => $issues->count() - $completedIssues->count(),
            'completed_issues' => $completedIssues->count(),
            'by_type' => $byType,
            'by_priority' => $byPriority,
            'by_column' => $byColumn,
            'recent_issues' => $recentIssues,
        ]);
    }
}