<?php

namespace App\Livewire\Profile;

use Livewire\Component;

class UpdateGithubToken extends Component
{
    public $githubToken;
    public $successMessage = null;

    public function mount()
    {
        $user = auth()->user();

        $this->githubToken = $user->getDetail('github_token');
    }

    public function saveToken()
    {
        $this->validate([
            'githubToken' => 'required|string',
        ]);

        $user = auth()->user();
        $user->upsertDetail('github_token', $this->githubToken);

        $this->successMessage = 'Your GitHub token has been updated successfully.';
    }

    public function render()
    {
        return view('livewire.profile.update-github-token');
    }
}
