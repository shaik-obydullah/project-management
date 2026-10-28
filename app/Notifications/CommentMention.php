<?php

namespace App\Notifications;

use App\Models\Issue;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CommentMention extends Notification
{
    use Queueable;

    public function __construct(public Issue $issue, public User $actor, public string $body)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'comment_mention',
            'title' => 'You were mentioned in a comment',
            'message' => $this->actor->name.' mentioned you on '.$this->issue->key,
            'issue_id' => $this->issue->id,
            'issue_key' => $this->issue->key,
            'issue_title' => $this->issue->title,
            'project_id' => $this->issue->project_id,
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
        ];
    }
}