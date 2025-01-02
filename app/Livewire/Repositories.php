<?php

namespace App\Livewire;

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

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function fetchRepositories()
    {
        $cacheKey = "repositories_page_{$this->page}_per_page_{$this->perPage}";

        // Fetch repositories from cache or API
        $repositories = Cache::remember($cacheKey, 3600, function () {
            try {
                $url = "https://api.github.com/user/repos";
                $response = Http::withToken(env('GITHUB_TOKEN'))
                    ->get($url, [
                        'per_page' => $this->perPage,
                        'page' => $this->page,
                    ]);

                if ($response->failed()) {
                    $this->errorMessage = "Failed to fetch repositories. Please try again.";
                    return collect();
                }

                return collect($response->json());
            } catch (\Exception $e) {
                $this->errorMessage = "An error occurred while fetching repositories.";
                return collect();
            }
        });

        // Apply search filter
        if ($this->search) {
            $repositories = $repositories->filter(function ($repo) {
                return str_contains(strtolower($repo['name']), strtolower($this->search));
            });
        }

        // Recalculate isLastPage dynamically
        $this->checkIfLastPage();

        return $repositories->values();
    }

    public function checkIfLastPage()
    {
        try {
            $url = "https://api.github.com/user/repos";
            $response = Http::withToken(env('GITHUB_TOKEN'))
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
