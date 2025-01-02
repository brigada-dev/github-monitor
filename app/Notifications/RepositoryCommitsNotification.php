<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\SlackMessage;

class RepositoryCommitsNotification extends Notification
{
    use Queueable;

    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function via($notifiable)
    {
        return ['slack'];
    }

    public function toSlack($notifiable)
    {
        $contributorsList = collect($this->data['contributors'])->map(function ($contributor) {
            return "{$contributor['author']}: {$contributor['commit_count']} commits";
        })->implode("\n");

        return (new SlackMessage)
            ->content("Daily Report for Repository: *{$this->data['repository_name']}*")
            ->attachment(function ($attachment) use ($contributorsList) {
                $attachment->title('View Repository', "https://github.com/{$this->data['repository_name']}")
                    ->fields([
                        'Commits Today' => $this->data['commit_count'],
                        'Contributors' => $contributorsList,
                    ]);
            });
    }
}
