<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Livewire\Component;

class Commits extends Component
{
    public $repoId;
    public $branch = null;
    public $branches = [];
    public $filterDate;
    public $contributors = [];
    public $errorMessage = null;
    public $github_token = null;

    public function mount($owner, $repo_name)
    {
        $this->repoId = "{$owner}/{$repo_name}";
        $this->filterDate = Carbon::now()->toDateString();
        $this->github_token = Auth::user()->getDetail('github_token');
    }

    public function fetchBranches()
    {
        try {
            $response = Http::withToken($this->github_token)
                ->get("https://api.github.com/repos/{$this->repoId}/branches");

            if ($response->ok()) {
                $this->branches = $response->json();
            } else {
                $this->branches = [];
                $this->errorMessage = "Failed to fetch branches.";
            }
        } catch (\Exception $e) {
            $this->branches = [];
            $this->errorMessage = "An error occurred while fetching branches.";
        }
    }

    public function fetchContributors()
    {
        if ($this->filterDate !== Carbon::now()->toDateString()) {
            $cacheKey = "{$this->repoId}_{$this->branch}_{$this->filterDate}";
            $this->contributors = Cache::remember($cacheKey, 86400, function () {
                return $this->fetchContributorsFromApi();
            });
        } else {
            $this->contributors = $this->fetchContributorsFromApi();
        }
    }

    private function fetchContributorsFromApi()
    {
        try {
            $response = Http::withToken($this->github_token)
                ->get("https://api.github.com/repos/{$this->repoId}/commits", [
                    'sha' => $this->branch,
                    'per_page' => 100,
                ]);

            if ($response->ok()) {
                $commits = collect($response->json());

                $filteredCommits = $commits->filter(function ($commit) {
                    $commitDate = Carbon::parse($commit['commit']['author']['date'])->toDateString();
                    return $commitDate === $this->filterDate;
                });

                return $filteredCommits->groupBy(function ($commit) {
                    return $commit['commit']['author']['name'] ?? 'Unknown';
                })->map(function ($commits, $author) {
                    return [
                        'author' => $author,
                        'commit_count' => count($commits),
                    ];
                })->values()->toArray();
            } else {
                $this->errorMessage = "Failed to fetch contributors.";
                return [];
            }
        } catch (\Exception $e) {
            $this->errorMessage = "An error occurred while fetching contributors.";
            return [];
        }
    }

    public function updatedBranch()
    {
        $this->fetchContributors();
    }

    public function updatedFilterDate()
    {
        $this->fetchContributors();
    }

    public function render()
    {
        $this->fetchBranches();
        $this->fetchContributors();

        return view('livewire.commits', [
            'branches' => $this->branches,
            'contributors' => $this->contributors,
            'errorMessage' => $this->errorMessage,
        ]);
    }
}
