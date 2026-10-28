<?php

namespace Database\Seeders;

use App\Models\Column;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\Label;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::firstOrCreate(
            ['email' => 'admin@projectflow.test'],
            ['name' => 'Demo Admin', 'password' => 'password']
        );

        $member = User::firstOrCreate(
            ['email' => 'jane@projectflow.test'],
            ['name' => 'Jane Developer', 'password' => 'password']
        );

        $third = User::firstOrCreate(
            ['email' => 'sam@projectflow.test'],
            ['name' => 'Sam Tester', 'password' => 'password']
        );

        $org = Organization::firstOrCreate(
            ['slug' => 'projectflow-inc'],
            ['name' => 'ProjectFlow Inc.', 'owner_id' => $owner->id]
        );

        $org->members()->syncWithoutDetaching([
            $owner->id => ['role' => 'owner'],
            $member->id => ['role' => 'member'],
            $third->id => ['role' => 'member'],
        ]);

        $project = Project::firstOrCreate(
            ['key' => 'WEB'],
            [
                'organization_id' => $org->id,
                'name' => 'Website Redesign',
                'description' => 'Redesign the company marketing website with a modern look and feel.',
                'color' => '#6366f1',
                'issue_prefix' => 'WEB',
                'issue_counter' => 0,
            ]
        );

        $project->members()->syncWithoutDetaching([
            $owner->id => ['role' => 'admin'],
            $member->id => ['role' => 'member'],
            $third->id => ['role' => 'member'],
        ]);

        $todo = Column::firstOrCreate(['project_id' => $project->id, 'name' => 'To Do'], ['position' => 0, 'color' => '#e2e8f0']);
        $progress = Column::firstOrCreate(['project_id' => $project->id, 'name' => 'In Progress'], ['position' => 1, 'color' => '#93c5fd']);
        $done = Column::firstOrCreate(['project_id' => $project->id, 'name' => 'Done'], ['position' => 2, 'color' => '#86efac']);

        $labels = collect([
            Label::firstOrCreate(['project_id' => $project->id, 'name' => 'Frontend'], ['color' => '#3b82f6']),
            Label::firstOrCreate(['project_id' => $project->id, 'name' => 'Backend'], ['color' => '#8b5cf6']),
            Label::firstOrCreate(['project_id' => $project->id, 'name' => 'Design'], ['color' => '#ec4899']),
            Label::firstOrCreate(['project_id' => $project->id, 'name' => 'Bug'], ['color' => '#ef4444']),
        ]);

        $sprint = Sprint::firstOrCreate(
            ['project_id' => $project->id, 'name' => 'Sprint 1'],
            ['goal' => 'Launch the new homepage and contact form.', 'status' => 'active']
        );

        $issues = [
            ['type' => 'story', 'title' => 'Design the new homepage hero section', 'priority' => 'high', 'column' => $progress, 'labels' => [2, 0], 'assignee' => $member->id],
            ['type' => 'task', 'title' => 'Set up responsive navigation menu', 'priority' => 'medium', 'column' => $todo, 'labels' => [2], 'assignee' => $owner->id],
            ['type' => 'bug', 'title' => 'Fix mobile menu overflow on iOS', 'priority' => 'urgent', 'column' => $progress, 'labels' => [3, 2], 'assignee' => $member->id],
            ['type' => 'task', 'title' => 'Build contact form with validation', 'priority' => 'high', 'column' => $todo, 'labels' => [1], 'assignee' => $third->id],
            ['type' => 'epic', 'title' => 'Overall site restructure', 'priority' => 'medium', 'column' => null, 'labels' => [0, 1], 'assignee' => null],
        ];

        $created = [];
        foreach ($issues as $index => $data) {
            $issue = Issue::firstOrCreate(
                ['key' => 'WEB-'.($index + 1)],
                [
                    'project_id' => $project->id,
                    'title' => $data['title'],
                    'type' => $data['type'],
                    'priority' => $data['priority'],
                    'description' => 'Detailed description for "'.$data['title'].'".',
                    'column_id' => $data['column']?->id,
                    'assigned_to' => $data['assignee'],
                    'sprint_id' => $sprint->id,
                    'created_by' => $owner->id,
                ]
            );
            $labelIds = collect($data['labels'])->map(fn ($i) => $labels[$i]->id)->all();
            $issue->labels()->sync($labelIds);
            $created[] = $issue;
        }

        $created[0]->update(['column_id' => $done->id]);
        $project->update(['issue_counter' => count($created)]);

        Comment::create([
            'issue_id' => $created[0]->id,
            'user_id' => $member->id,
            'body' => 'Initial designs look great, let me know what you think! @Demo Admin',
        ]);

        Comment::create([
            'issue_id' => $created[0]->id,
            'user_id' => $owner->id,
            'body' => 'Love it. Can we make the hero image larger?',
        ]);
    }
}