<?php

namespace App\Livewire\Profile;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class UpdateSocialLinks extends Component
{
    public string $website = '';
    public string $linkedin_url = '';
    public string $github_url = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->website = $user->website ?? '';
        $this->linkedin_url = $user->linkedin_url ?? '';
        $this->github_url = $user->github_url ?? '';
    }

    public function rules()
    {
        return [
            'website' => ['nullable', 'url', function ($attribute, $value, $fail) {
                if ($value && !$this->isValidWebsite($value)) {
                    $fail('The website URL format is invalid.');
                }
            }],
            'linkedin_url' => ['nullable', 'url', function ($attribute, $value, $fail) {
                if ($value && !$this->isValidLinkedInUrl($value)) {
                    $fail('Please enter a valid LinkedIn profile URL (e.g., https://linkedin.com/in/yourprofile).');
                }
            }],
            'github_url' => ['nullable', 'url', function ($attribute, $value, $fail) {
                if ($value && !$this->isValidGitHubUrl($value)) {
                    $fail('Please enter a valid GitHub profile URL (e.g., https://github.com/yourusername).');
                }
            }],
        ];
    }

    /**
     * Validate if the URL is a valid LinkedIn profile URL
     */
    private function isValidLinkedInUrl($url)
    {
        // Remove trailing slash for consistency
        $url = rtrim($url, '/');
        
        // Check if it's a LinkedIn URL
        $pattern = '/^https?:\/\/(www\.)?linkedin\.com\/in\/[a-zA-Z0-9\-_]+\/?$/';
        return preg_match($pattern, $url);
    }

    /**
     * Validate if the URL is a valid GitHub profile URL
     */
    private function isValidGitHubUrl($url)
    {
        // Remove trailing slash for consistency
        $url = rtrim($url, '/');
        
        // Check if it's a GitHub URL
        $pattern = '/^https?:\/\/(www\.)?github\.com\/[a-zA-Z0-9\-_\.]+\/?$/';
        return preg_match($pattern, $url);
    }

    /**
     * Validate if the URL is a valid website URL
     */
    private function isValidWebsite($url)
    {
        // Basic URL validation - must be a valid URL and not LinkedIn or GitHub
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        // Check it's not a LinkedIn or GitHub URL (those should go in their respective fields)
        $domain = parse_url($url, PHP_URL_HOST);
        $domain = strtolower(str_replace('www.', '', $domain));
        
        $socialDomains = ['linkedin.com', 'github.com', 'facebook.com', 'twitter.com', 'instagram.com'];
        
        return !in_array($domain, $socialDomains);
    }

    /**
     * Update the social links for the currently authenticated user.
     */
    public function updateSocialLinks(): void
    {
        $validated = $this->validate();

        $user = Auth::user();
        $user->update($validated);

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Auto-format URLs when user leaves the field
     */
    public function updatedLinkedinUrl()
    {
        if ($this->linkedin_url && !empty(trim($this->linkedin_url))) {
            $this->linkedin_url = $this->formatLinkedInUrl($this->linkedin_url);
        }
    }

    public function updatedGithubUrl()
    {
        if ($this->github_url && !empty(trim($this->github_url))) {
            $this->github_url = $this->formatGitHubUrl($this->github_url);
        }
    }

    public function updatedWebsite()
    {
        if ($this->website && !empty(trim($this->website))) {
            $this->website = $this->formatWebsiteUrl($this->website);
        }
    }

    /**
     * Auto-format LinkedIn URL to correct format
     */
    private function formatLinkedInUrl($url)
    {
        $url = trim($url);
        
        // Add https:// if missing
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = 'https://' . $url;
        }
        
        // Convert to linkedin.com/in/ format
        $url = preg_replace('/^(https?:\/\/)(www\.)?linkedin\.com\/profile\/view\?id=/', '$1linkedin.com/in/', $url);
        $url = preg_replace('/^(https?:\/\/)(www\.)?linkedin\.com\/pub\//', '$1linkedin.com/in/', $url);
        
        // Remove www. from LinkedIn URLs
        $url = preg_replace('/^(https?:\/\/)www\.linkedin\.com/', '$1linkedin.com', $url);
        
        return $url;
    }

    /**
     * Auto-format GitHub URL to correct format
     */
    private function formatGitHubUrl($url)
    {
        $url = trim($url);
        
        // Add https:// if missing
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = 'https://' . $url;
        }
        
        // Remove www. from GitHub URLs
        $url = preg_replace('/^(https?:\/\/)www\.github\.com/', '$1github.com', $url);
        
        return $url;
    }

    /**
     * Auto-format website URL
     */
    private function formatWebsiteUrl($url)
    {
        $url = trim($url);
        
        // Add https:// if missing
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = 'https://' . $url;
        }
        
        return $url;
    }

    public function render()
    {
        return view('livewire.profile.update-social-links');
    }
}
