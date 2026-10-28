<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\Column;
use App\Models\Issue;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use App\Notifications\IssueAssigned;

class IssueController extends Controller
{
    public function index(Request $request, Project $project)
    {
        abort_unless($project->isMember($request->user()), 403, 'You are not a member of this project.');

        $query = $project->issues()
            ->with(['assignee:id,name,email,avatar', 'creator:id,name,email', 'labels:id,name,color', 'sprint:id,name', 'column:id,name']);

        if ($request->has('sprint_id')) {
            $query->where('sprint_id', $request->sprint_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('assignee')) {
            $query->where('assigned_to', $request->assignee);
        }

        if ($request->has('label_id')) {
            $query->whereHas('labels', fn ($q) => $q->where('labels.id', $request->label_id));
        }

        $issues = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 50));

        return response()->json(['issues' => $issues]);
    }

    public function store(Request $request, Project $project)
    {
        abort_unless($project->isMember($request->user()), 403, 'You are not a member of this project.');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:epic,story,task,bug,subtask'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'column_id' => ['nullable', 'exists:columns,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'parent_id' => ['nullable', 'exists:issues,id'],
            'estimate' => ['nullable', 'numeric', 'min:0'],
            'due_at' => ['nullable', 'date'],
            'sprint_id' => ['nullable', 'exists:sprints,id'],
        ]);

        $issue = Issue::create([
            'project_id' => $project->id,
            'key' => $project->issue_prefix.'-'.($project->nextIssueNumber()),
            'title' => $validated['title'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'priority' => $validated['priority'],
            'column_id' => $validated['column_id'] ?? null,
            'assigned_to' => $validated['assigned_to'] ?? null,
            'created_by' => $request->user()->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'estimate' => $validated['estimate'] ?? null,
            'due_at' => $validated['due_at'] ?? null,
            'sprint_id' => $validated['sprint_id'] ?? null,
        ]);

        if ($issue->assigned_to) {
            $this->notifyAssignee($issue, $request->user());
        }

        activity()
            ->causedBy($request->user())
            ->performedOn($issue)
            ->event('created')
            ->log('issue created');

        $issue->load(['assignee:id,name,email,avatar', 'creator:id,name,email', 'labels', 'sprint:id,name']);

        return response()->json([
            'message' => 'Issue created',
            'issue' => $issue,
        ], 201);
    }

    public function show(Request $request, Issue $issue)
    {
        abort_unless($issue->project->isMember($request->user()), 403, 'You are not a member of this project.');

        $issue->load([
            'project:id,name,key,color',
            'assignee:id,name,email,avatar',
            'creator:id,name,email,avatar',
            'labels:id,name,color',
            'parent:id,key,title',
            'subtasks' => fn ($q) => $q->select('id', 'parent_id', 'key', 'title', 'column_id')->with('column:id,name'),
            'sprint:id,name,status',
            'comments' => fn ($q) => $q->with(['user:id,name,email,avatar', 'replies.user:id,name,email,avatar'])->whereNull('parent_id')->orderBy('created_at', 'desc'),
            'attachments' => fn ($q) => $q->with('user:id,name'),
        ]);

        $activity = \Spatie\Activitylog\Models\Activity::forSubject($issue)
            ->with('causer:id,name,email,avatar')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'issue' => $issue,
            'activity' => $activity,
        ]);
    }

    public function update(Request $request, Issue $issue)
    {
        abort_unless($issue->project->isMember($request->user()), 403, 'You are not a member of this project.');

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['sometimes', 'in:epic,story,task,bug,subtask'],
            'priority' => ['sometimes', 'in:low,medium,high,urgent'],
            'column_id' => ['sometimes', 'nullable', 'exists:columns,id'],
            'assigned_to' => ['sometimes', 'nullable', 'exists:users,id'],
            'parent_id' => ['sometimes', 'nullable', 'exists:issues,id'],
            'estimate' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'due_at' => ['sometimes', 'nullable', 'date'],
            'sprint_id' => ['sometimes', 'nullable', 'exists:sprints,id'],
        ]);

        $oldAssignee = $issue->assigned_to;
        $issue->update($validated);

        if ($issue->wasChanged('assigned_to') && $issue->assigned_to && isset($oldAssignee)) {
            if ($issue->assigned_to !== $oldAssignee) {
                $this->notifyAssignee($issue, $request->user());
            }
        }

        activity()
            ->causedBy($request->user())
            ->performedOn($issue)
            ->event('updated')
            ->withProperties(['changes' => $issue->getChanges()])
            ->log('issue updated');

        $issue->load(['assignee:id,name,email,avatar', 'creator:id,name,email', 'labels', 'sprint:id,name']);

        return response()->json([
            'message' => 'Issue updated',
            'issue' => $issue,
        ]);
    }

    public function destroy(Request $request, Issue $issue)
    {
        abort_unless($issue->project->isMember($request->user()), 403, 'You are not a member of this project.');

        $issue->delete();

        activity()
            ->causedBy($request->user())
            ->performedOn($issue)
            ->event('deleted')
            ->log('issue deleted');

        return response()->json(['message' => 'Issue deleted']);
    }

    public function move(Request $request, Issue $issue)
    {
        abort_unless($issue->project->isMember($request->user()), 403, 'You are not a member of this project.');

        $validated = $request->validate([
            'column_id' => ['required', 'exists:columns,id'],
            'position' => ['required', 'integer', 'min:0'],
        ]);

        $oldColumnId = $issue->column_id;
        $issue->update([
            'column_id' => $validated['column_id'],
            'position' => $validated['position'],
        ]);

        activity()
            ->causedBy($request->user())
            ->performedOn($issue)
            ->event('moved')
            ->withProperties([
                'from_column' => $oldColumnId,
                'to_column' => $validated['column_id'],
            ])
            ->log('issue moved');

        return response()->json([
            'message' => 'Issue moved',
            'issue' => $issue->load('assignee:id,name,avatar', 'labels:id,name,color', 'creator:id,name'),
        ]);
    }

    public function syncLabels(Request $request, Issue $issue)
    {
        abort_unless($issue->project->isMember($request->user()), 403, 'You are not a member of this project.');

        $validated = $request->validate([
            'label_ids' => ['required', 'array'],
            'label_ids.*' => ['integer', 'exists:labels,id'],
        ]);

        $issue->labels()->sync($validated['label_ids']);

        return response()->json([
            'message' => 'Labels updated',
            'labels' => $issue->labels()->get(['labels.id', 'labels.name', 'labels.color']),
        ]);
    }

    public function attachments(Request $request, Issue $issue)
    {
        abort_unless($issue->project->isMember($request->user()), 403, 'You are not a member of this project.');

        return response()->json(['attachments' => $issue->attachments()->with('user:id,name')->get()]);
    }

    public function uploadAttachment(Request $request, Issue $issue)
    {
        abort_unless($issue->project->isMember($request->user()), 403, 'You are not a member of this project.');

        $validated = $request->validate([
            'files' => ['required', 'array'],
            'files.*' => ['file', 'max:20480'],
        ]);

        $attachments = [];

        foreach ($request->file('files') as $file) {
            $path = $file->store('attachments/'.$issue->id, 'public');

            $attachment = Attachment::create([
                'issue_id' => $issue->id,
                'user_id' => $request->user()->id,
                'filename' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);

            $attachments[] = $attachment->load('user:id,name');
        }

        activity()
            ->causedBy($request->user())
            ->performedOn($issue)
            ->event('attachment_added')
            ->log('attachment added');

        return response()->json([
            'message' => 'Files uploaded',
            'attachments' => $attachments,
        ], 201);
    }

    public function deleteAttachment(Request $request, Attachment $attachment)
    {
        abort_unless($attachment->issue->project->isMember($request->user()), 403, 'You are not a member of this project.');

        \Illuminate\Support\Facades\Storage::disk('public')->delete($attachment->path);
        $attachment->delete();

        return response()->json(['message' => 'Attachment deleted']);
    }

    private function notifyAssignee(Issue $issue, $actor): void
    {
        $assignee = $issue->assignee;
        if ($assignee && $assignee->id !== $actor->id) {
            $assignee->notify(new IssueAssigned($issue, $actor));
        }
    }
}