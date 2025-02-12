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
    public $errorMessage = null;
    public $isLastPage = false;
    public $favoriteRepositories = [];
    public $githubToken;
    public $notificationMethod = 'email';
    public $notificationOptions = ['email', 'discord', 'slack'];
    public $notificationTrigger = '';
    public $availableBranches = [];
    public $selectedBranches = [];
    public $showBranchesDropdown = false;

    public $notificationDetails = [
        'email' => '',
        'discord' => '',
        'slack' => '',
    ];

    protected $rules = [
        'notificationTrigger' => 'required|string|email:rfc,dns|url', // General structure; will adjust dynamically
    ];

    protected function getValidationRules()
    {
        return [
            'notificationTrigger' => match ($this->notificationMethod) {
                'email' => 'required|email',
                'discord', 'slack' => 'required|url',
                'selectedBranches' => 'required|array|min:1',
                default => 'required|string',
            },
        ];
    }

    public function toggleBranchesDropdown()
    {
        $this->showBranchesDropdown = !$this->showBranchesDropdown;
    }


    public $showModal = false;
    public $selectedRepository = null;

    public function mount()
    {
        $user = auth()->user();

        $this->githubToken = $user->getDetail('github_token');

        if (!$this->githubToken) {
            $this->errorMessage = "GitHub token is missing. Please provide your token.";
        }

        $this->favoriteRepositories = FavoriteRepository::where('user_id', auth()->id())
            ->pluck('repository_name')
            ->toArray();
    }

    public function showNotificationModal($repositoryName)
    {
        $this->selectedRepository = $repositoryName;
        $this->fetchBranches($repositoryName);
        $this->showModal = true;
    }

    public function toggleFavorite($full_name_repo = null)
    {
        if($full_name_repo != null) {
            $this->selectedRepository = $full_name_repo;
        }else{
            $this->validate($this->getValidationRules());
        }
        if (!$this->selectedRepository) {
            $this->errorMessage = "No repository selected.";
            return;
        }

        $favorite = FavoriteRepository::where('user_id', auth()->id())
            ->where('repository_name', $this->selectedRepository)
            ->first();

        if ($favorite) {
            $favorite->delete();
            $this->favoriteRepositories = array_diff($this->favoriteRepositories, [$this->selectedRepository]);
        } else {
            FavoriteRepository::create([
                'user_id' => auth()->id(),
                'repository_name' => $this->selectedRepository,
                'notification_method' => $this->notificationMethod,
                'notification_trigger' => $this->notificationTrigger,
                'branches' => $this->selectedBranches,
            ]);
            $this->favoriteRepositories[] = $this->selectedRepository;
        }

        $this->resetInputFields();
        $this->showModal = false;
        $this->selectedRepository = null;
    }

    public function resetInputFields()
    {
        $this->notificationTrigger = '';
        $this->selectedBranches = [];
        $this->availableBranches = [];
    }

    public function toggleBranch($branch)
    {
        if (in_array($branch, $this->selectedBranches)) {
            $this->selectedBranches = array_filter($this->selectedBranches, fn($b) => $b !== $branch);
        } else {
            $this->selectedBranches[] = $branch;
        }
    }

    public function setGitHubToken()
    {
        $user = auth()->user();

        $user->upsertDetail('github_token', $this->githubToken);

        $this->errorMessage = null;
        $this->dispatch('token-saved', ['message' => 'GitHub token saved successfully!']);
    }

    public function fetchBranches($repositoryName)
    {
        if (!$this->githubToken) {
            $this->errorMessage = "GitHub token is missing.";
            return;
        }

        try {
            $response = Http::withToken($this->githubToken)
                ->get("https://api.github.com/repos/{$repositoryName}/branches");

            if ($response->failed()) {
                $this->errorMessage = "Failed to fetch branches for {$repositoryName}.";
                return;
            }

            $this->availableBranches = collect($response->json())->pluck('name')->toArray();
        } catch (\Exception $e) {
            $this->errorMessage = "Error fetching branches for {$repositoryName}.";
        }
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
                    'page' => $this->page + 1,
                ]);

            $this->isLastPage = $response->json() === [];
        } catch (\Exception $e) {
            $this->isLastPage = true;
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
            'favoriteRepositories' => $this->favoriteRepositories
        ]);
    }
}
