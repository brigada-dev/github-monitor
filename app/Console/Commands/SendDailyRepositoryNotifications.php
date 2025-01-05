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
    protected $description = 'Send daily notifications for favorite repositories';

    public function handle()
    {
        $favorites = FavoriteRepository::all();

        foreach ($favorites as $favorite) {
            $branches = $favorite->branches ?? []; // Use specified branches or fetch from all
            $commits = $this->getTodayCommits($favorite->repository_name, $branches);

            if (!empty($commits)) {
                $data = [
                    'repository_name' => $favorite->repository_name,
                    'commit_count' => count($commits),
                    'contributors' => $this->groupCommitsByContributor($commits),
                ];
            } else {
                $data = [
                    'repository_name' => $favorite->repository_name,
                    'commit_count' => 0,
                    'contributors' => [],
                    'message' => "No commits were made to the specified branches today.",
                ];
            }

            $this->sendNotification($favorite, $data);
        }

        return 0;
    }

    private function getTodayCommits($repositoryName, $branches = [])
    {
        try {
            $allCommits = [];

            if (empty($branches)) {
                // If no branches specified, fetch from the default branch
                $branchesResponse = Http::withToken(env('GITHUB_TOKEN'))
                    ->get("https://api.github.com/repos/{$repositoryName}/branches");

                if ($branchesResponse->failed()) {
                    Log::error("Failed to fetch branches for {$repositoryName}: {$branchesResponse->body()}");
                    return [];
                }

                $branches = collect($branchesResponse->json())->pluck('name')->toArray();
            }

            foreach ($branches as $branch) {
                $response = Http::withToken(env('GITHUB_TOKEN'))
                    ->get("https://api.github.com/repos/{$repositoryName}/commits", [
                        'sha' => $branch,
                        'since' => Carbon::now('UTC')->startOfDay()->toIso8601String(),
                        'until' => Carbon::now('UTC')->endOfDay()->toIso8601String(),
                    ]);

                if ($response->failed()) {
                    Log::error("Failed to fetch commits for {$repositoryName} on branch {$branch}: {$response->body()}");
                    continue;
                }

                $commits = $response->json();
                $allCommits = array_merge($allCommits, $commits);
            }

            // Deduplicate commits by SHA
            return collect($allCommits)->unique('sha')->values()->toArray();
        } catch (\Exception $e) {
            Log::error("Error fetching commits for {$repositoryName}: {$e->getMessage()}");
            return [];
        }
    }

    private function groupCommitsByContributor($commits)
    {
        return collect($commits)
            ->groupBy(fn($commit) => $commit['commit']['author']['name'] ?? 'Unknown')
            ->map(fn($commits, $author) => [
                'author' => $author,
                'commit_count' => count($commits),
            ])
            ->values()
            ->toArray();
    }

    private function sendNotification($favorite, $data)
    {
        $method = $favorite->notification_method;
        $trigger = $favorite->notification_trigger;

        if ($method === 'discord') {
            $this->sendDiscordNotification($trigger, $data);
        } else {
            $channel = $method === 'email' ? 'mail' : $method;

            Notification::route($channel, $trigger)
                ->notify(new RepositoryCommitsNotification($data, $channel));
        }
    }

    private function sendDiscordNotification($webhookUrl, $data)
    {
        $contributorsList = collect($data['contributors'])->map(function ($contributor) {
            return "{$contributor['author']}: {$contributor['commit_count']} commits";
        })->implode("\n");

        $payload = [
            'content' => $data['commit_count'] > 0
                ? "Daily Report for Repository: **{$data['repository_name']}**"
                : "No commits found for **{$data['repository_name']}** today.",
            'embeds' => $data['commit_count'] > 0 ? [
                [
                    'title' => 'View Repository',
                    'url' => "https://github.com/{$data['repository_name']}",
                    'fields' => [
                        ['name' => 'Commits Today', 'value' => $data['commit_count']],
                        ['name' => 'Contributors', 'value' => $contributorsList ?: 'No contributors today'],
                    ],
                ],
            ] : [],
        ];

        try {
            $response = Http::post($webhookUrl, $payload);

            if ($response->failed()) {
                Log::error("Failed to send Discord notification: {$response->body()}");
            }
        } catch (\Exception $e) {
            Log::error("Error sending Discord notification: {$e->getMessage()}");
        }
    }
}
