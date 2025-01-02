<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Models\FavoriteRepository;
use App\Notifications\RepositoryCommitsNotification;
use Carbon\Carbon;

class SendDailyRepositoryNotifications extends Command
{
    protected $signature = 'repositories:send-daily-notifications';
    protected $description = 'Send daily Slack notifications for favorite repositories';

    public function handle()
    {
        // Fetch all favorite repositories
        $favorites = FavoriteRepository::all();

        foreach ($favorites as $favorite) {
            $repositoryName = $favorite->repository_name;
            $commits = $this->getTodayCommits($repositoryName);
            Log::info($commits);
            if (!empty($commits)) {
                $contributors = $this->groupCommitsByContributor($commits);

                $data = [
                    'repository_name' => $repositoryName,
                    'commit_count' => count($commits),
                    'contributors' => $contributors,
                ];

                // Send Slack notification using the global webhook URL
                Notification::route('slack', env('SLACK_WEBHOOK_URL'))
                    ->notify(new RepositoryCommitsNotification($data));
            }
        }

        return 0;
    }

    private function getTodayCommits($repositoryName)
    {
        try {
            $today = Carbon::now()->toDateString();
            $response = Http::withToken(env('GITHUB_TOKEN'))
                ->get("https://api.github.com/repos/{$repositoryName}/commits", [
                    'since' => "{$today}T00:00:00Z", // Get commits since start of today
                    'until' => "{$today}T23:59:59Z", // Get commits until end of today
                ]);

            return $response->ok() ? $response->json() : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    private function groupCommitsByContributor($commits)
    {
        return collect($commits)
            ->groupBy(function ($commit) {
                return $commit['commit']['author']['name'] ?? 'Unknown';
            })
            ->map(function ($commits, $author) {
                return [
                    'author' => $author,
                    'commit_count' => count($commits),
                ];
            })
            ->values()
            ->toArray();
    }
}
