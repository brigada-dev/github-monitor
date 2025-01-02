<?php

namespace App\Livewire;

use App\Models\FavoriteRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

class Repositories extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 9;
    public $page = 1;
    public $errorMessage = null; // To handle errors
    public $isLastPage = false; // Flag to check if it's the last page
    public $favoriteRepositories = [];
    public $githubToken; // Temporarily hold the GitHub token

    public function mount()
    {
        $user = auth()->user();

        // Load the GitHub token from user details
        $this->githubToken = $user->getDetail('github_token');

        if (!$this->githubToken) {
            $this->errorMessage = "GitHub token is missing. Please provide your token.";
        }

        // Load favorite repositories
        $this->favoriteRepositories = FavoriteRepository::where('user_id', auth()->id())
            ->pluck('repository_name')
            ->toArray();
    }

    public function setGitHubToken()
    {
        $user = auth()->user();

        // Save or update the GitHub token
        $user->upsertDetail('github_token', $this->githubToken);

        $this->errorMessage = null; // Clear any previous error message
        $this->dispatch('token-saved', ['message' => 'GitHub token saved successfully!']);
    }

    public function fetchRepositories()
    {
        if (!$this->githubToken) {
            $this->errorMessage = "GitHub token is missing. Please provide your token.";
            return collect();
        }

        $cacheKey = "repositories_page_{$this->page}_per_page_{$this->perPage}";

        $repositories = Cache::remember($cacheKey, 3600, function () {
            try {
                $url = "https://api.github.com/user/repos";
                $response = Http::withToken($this->githubToken)
                    ->get($url, [
                        'per_page' => $this->perPage,
                        'page' => $this->page,
                    ]);

                if ($response->failed()) {
                    $this->errorMessage = "Failed to fetch repositories. Please check your GitHub token.";
                    return collect();
                }

                return collect($response->json());
            } catch (\Exception $e) {
                $this->errorMessage = "An error occurred while fetching repositories.";
                return collect();
            }
        });

        if ($this->search) {
            $repositories = $repositories->filter(function ($repo) {
                return str_contains(strtolower($repo['name']), strtolower($this->search));
            });
        }

        $this->checkIfLastPage();
        return $repositories->values();
    }

    public function checkIfLastPage()
    {
        try {
            $url = "https://api.github.com/user/repos";
            $response = Http::withToken($this->githubToken)
                ->get($url, [
                    'per_page' => $this->perPage,
                    'page' => $this->page + 1, // Check the next page
                ]);

            $this->isLastPage = $response->json() === [];
        } catch (\Exception $e) {
            $this->isLastPage = true; // Assume it's the last page on error
        }
    }

    public function previousPage()
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }

    public function nextPage()
    {
        if (!$this->isLastPage) {
            $this->page++;
        }
    }

    public function render()
    {
        $repositories = $this->fetchRepositories();

        return view('livewire.repositories', [
            'repositories' => $repositories,
            'errorMessage' => $this->errorMessage,
            'isLastPage' => $this->isLastPage,
        ]);
    }
}
