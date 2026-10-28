<?php

namespace App\Notifications;

use App\Models\Issue;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class IssueAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Issue $issue, public User $actor)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'issue_assigned',
            'title' => 'Issue assigned to you',
            'message' => $this->actor->name.' assigned '.$this->issue->key.' to you',
            'issue_id' => $this->issue->id,
            'issue_key' => $this->issue->key,
            'issue_title' => $this->issue->title,
            'project_id' => $this->issue->project_id,
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
        ];
    }
}