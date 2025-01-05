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

    /**
     * Build the Mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        if ($this->data['total_commits'] > 0) {
            return $this->buildMailWithCommits();
        }

        return $this->buildMailNoCommits();
    }

    /**
     * Build a mail message when there are commits
     */
    private function buildMailWithCommits()
    {
        $mail = (new MailMessage)
            ->subject("Daily Report for Repository: {$this->data['repository_name']}")
            ->line("Total Commits Today: {$this->data['total_commits']}");

        foreach ($this->data['branch_data'] as $branchInfo) {
            $mail->line("**Branch: {$branchInfo['branch_name']}**");
            $mail->line("Commits: {$branchInfo['commit_count']}");

            $contributorsList = collect($branchInfo['contributors'])->map(function ($contributor) {
                return "- {$contributor['author']}: {$contributor['commit_count']} commits";
            })->implode("\n");

            if (!empty($contributorsList)) {
                $mail->line("Contributors:\n{$contributorsList}");
            }

            $mail->line('');
        }

        return $mail->action('View Repository', "https://github.com/{$this->data['repository_name']}");
    }

    /**
     * Build a mail message when there are NO commits
     */
    private function buildMailNoCommits()
    {
        return (new MailMessage)
            ->subject("Daily Report for Repository: {$this->data['repository_name']}")
            ->line("No commits were made to the repository today.")
            ->action('View Repository', "https://github.com/{$this->data['repository_name']}");
    }

    /**
     * Build the Slack representation of the notification.
     */
    public function toSlack($notifiable)
    {
        if ($this->data['total_commits'] === 0) {
            return (new SlackMessage)->http(['verify' => false])
                ->content("No commits were made to **{$this->data['repository_name']}** today.");
        }

        return (new SlackMessage)->http(['verify' => false])
            ->content("Daily Report for Repository: *{$this->data['repository_name']}*")
            ->attachment(function ($attachment) {
                $fields = [];
                foreach ($this->data['branch_data'] as $branchInfo) {
                    $contributorsList = collect($branchInfo['contributors'])->map(function ($contributor) {
                        return "{$contributor['author']}: {$contributor['commit_count']}";
                    })->implode("\n");

                    $fields["Branch: {$branchInfo['branch_name']}"] =
                        "Commits: {$branchInfo['commit_count']}\n{$contributorsList}";
                }

                $attachment->title('View Repository', "https://github.com/{$this->data['repository_name']}")
                    ->fields($fields);
            });
    }
}
