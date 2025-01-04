<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Messages\MailMessage;

class RepositoryCommitsNotification extends Notification
{
    use Queueable;

    private $data;
    private $method;

    public function __construct($data, $method)
    {
        $this->data = $data;
        $this->method = $method;
    }

    public function via($notifiable)
    {
        return [$this->method];
    }

    public function toMail($notifiable)
    {
        if ($this->data['commit_count'] > 0) {
            $contributorsList = collect($this->data['contributors'])->map(function ($contributor) {
                return "{$contributor['author']}: {$contributor['commit_count']} commits";
            })->implode("\n");

            return (new MailMessage)
                ->subject("Daily Report for Repository: {$this->data['repository_name']}")
                ->line("Commits Today: {$this->data['commit_count']}")
                ->line("Contributors:")
                ->line($contributorsList)
                ->action('View Repository', "https://github.com/{$this->data['repository_name']}");
        }

        return (new MailMessage)
            ->subject("Daily Report for Repository: {$this->data['repository_name']}")
            ->line("No commits were made to the repository today.")
            ->action('View Repository', "https://github.com/{$this->data['repository_name']}");
    }

    public function toSlack($notifiable)
    {
        $contributorsList = collect($this->data['contributors'])->map(function ($contributor) {
            return "{$contributor['author']}: {$contributor['commit_count']} commits";
        })->implode("\n");

        return (new SlackMessage)->http(['verify'=> false])
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
