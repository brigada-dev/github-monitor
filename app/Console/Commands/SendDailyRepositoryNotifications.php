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
            $branches = $favorite->branches ?? [];
            if (empty($branches)) {
                $branches = $this->fetchAllBranches($favorite->repository_name);
            }

            $branchCommits = $this->getTodayCommits($favorite->repository_name, $branches);
            if (empty($branchCommits)) {
                $data = [
                    'repository_name' => $favorite->repository_name,
                    'total_commits'   => 0,
                    'branch_data'     => [],
                    'message'         => "No commits were made to this repository today.",
                ];
            } else {
                // Otherwise, construct a detailed summary of commits per branch
                $data = $this->compileBranchData($favorite->repository_name, $branchCommits);
            }

            $this->sendNotification($favorite, $data);
        }

        return 0;
    }

    /**
     * Fetch all branches for a given repository (if no branches are specified).
     */
    private function fetchAllBranches($repositoryName)
    {
        try {
            $branchesResponse = Http::withToken(env('GITHUB_TOKEN'))
                ->get("https://api.github.com/repos/{$repositoryName}/branches");

            if ($branchesResponse->failed()) {
                Log::error("Failed to fetch branches for {$repositoryName}: {$branchesResponse->body()}");
                return [];
            }

            return collect($branchesResponse->json())->pluck('name')->toArray();
        } catch (\Exception $e) {
            Log::error("Error fetching branches for {$repositoryName}: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Get all commits for "today" per branch.
     *
     * @return array  Example structure:
     *               [
     *                 'main' => [ {commit1}, {commit2}, ... ],
     *                 'develop' => [ ... ],
     *               ]
     */
    private function getTodayCommits($repositoryName, array $branches = [])
    {
        $branchCommits = [];

        foreach ($branches as $branch) {
            try {
                $response = Http::withToken(env('GITHUB_TOKEN'))->get(
                    "https://api.github.com/repos/{$repositoryName}/commits",
                    [
                        'sha'   => $branch,
                        'since' => Carbon::now('UTC')->startOfMonth()->toIso8601String(),
                        'until' => Carbon::now('UTC')->endOfMonth()->toIso8601String(),
                    ]
                );

                if ($response->failed()) {
                    Log::error("Failed to fetch commits for {$repositoryName} on branch {$branch}: {$response->body()}");
                    continue;
                }

                $commits = $response->json();
                if (!empty($commits)) {
                    $uniqueCommits = collect($commits)->unique('sha')->values()->toArray();
                    $branchCommits[$branch] = $uniqueCommits;
                }
            } catch (\Exception $e) {
                Log::error("Error fetching commits for {$repositoryName} on branch {$branch}: {$e->getMessage()}");
            }
        }

        return $branchCommits;
    }

    /**
     * Build a nice summary of all branches and commits for the notification.
     */
    private function compileBranchData($repositoryName, array $branchCommits)
    {
        $branchData = [];
        $totalCommits = 0;

        foreach ($branchCommits as $branchName => $commits) {
            $branchData[] = [
                'branch_name' => $branchName,
                'commit_count' => count($commits),
                'contributors' => $this->groupCommitsByContributor($commits),
                'commits' => $commits,
            ];

            $totalCommits += count($commits);
        }

        return [
            'repository_name' => $repositoryName,
            'total_commits'   => $totalCommits,
            'branch_data'     => $branchData,
        ];
    }

    /**
     * Group commits by author/contributor.
     */
    private function groupCommitsByContributor($commits)
    {
        return collect($commits)
            ->groupBy(fn($commit) => $commit['commit']['author']['name'] ?? 'Unknown')
            ->map(fn($commits, $author) => [
                'author'       => $author,
                'commit_count' => count($commits),
            ])
            ->values()
            ->toArray();
    }

    /**
     * Dispatch to the correct channel (Discord, Email, Slack, etc.)
     */
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
        $branchFields = [];
        if (!empty($data['branch_data'])) {
            foreach ($data['branch_data'] as $branchInfo) {
                $contributorsList = collect($branchInfo['contributors'])->map(function ($contributor) {
                    return "{$contributor['author']}: {$contributor['commit_count']} commits";
                })->implode("\n");

                $branchFields[] = [
                    'name'  => "Branch: {$branchInfo['branch_name']}",
                    'value' => "Commits: {$branchInfo['commit_count']}\n{$contributorsList}",
                ];
            }
        }

        $payload = [
            'content' => $data['total_commits'] > 0
                ? "Daily Report for **{$data['repository_name']}**"
                : "No commits found for **{$data['repository_name']}** today.",
            'embeds' => $data['total_commits'] > 0 ? [
                [
                    'title'  => "View Repository: {$data['repository_name']}",
                    'url'    => "https://github.com/{$data['repository_name']}",
                    'fields' => $branchFields,
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
