<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
            <div>
                <flux:heading size="xl" class="mb-2">
                    <flux:icon.sparkles class="mr-2 text-purple-500" />
                    AI Job Rating Details
                </flux:heading>
                <p class="text-zinc-600 dark:text-zinc-400">
                    Detailed view of the AI analysis for Job ID: {{ $rating->job_id }}
                </p>
            </div>
            <flux:button
                href="{{ route('job-ratings.index') }}"
                variant="outline"
                icon="arrow-left"
                size="sm"
            >
                Back to History
            </flux:button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column - Job Info & Metadata -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Job Information -->
                @if($jobPosting)
                <flux:card>
                    <div class="p-4">
                        <flux:heading size="md" class="mb-4 text-blue-600">
                            <flux:icon.briefcase class="mr-2" />
                            Job Information
                        </flux:heading>
                        
                        <div class="space-y-3 text-sm">
                            <div>
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">Title:</span>
                                <span class="block text-zinc-600 dark:text-zinc-400">{{ $jobPosting->title ?? 'N/A' }}</span>
                            </div>
                            
                            @if($jobPosting->company)
                            <div>
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">Company:</span>
                                <span class="block text-zinc-600 dark:text-zinc-400">{{ $jobPosting->company->name ?? 'N/A' }}</span>
                            </div>
                            @endif
                            
                            <div>
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">Location:</span>
                                <span class="block text-zinc-600 dark:text-zinc-400">{{ $jobPosting->location ?? 'N/A' }}</span>
                            </div>
                            
                            @if($jobPosting->work_type)
                            <div>
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">Work Type:</span>
                                <span class="block text-zinc-600 dark:text-zinc-400">{{ $jobPosting->work_type }}</span>
                            </div>
                            @endif

                            @if($jobPosting->applicants)
                            <div>
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">Applicants:</span>
                                <span class="block text-zinc-600 dark:text-zinc-400">{{ number_format($jobPosting->applicants) }}</span>
                            </div>
                            @endif

                            @if($jobPosting->apply_url)
                            <div class="pt-2">
                                <flux:button
                                    size="sm"
                                    href="{{ $jobPosting->apply_url }}"
                                    target="_blank"
                                    icon="arrow-top-right-on-square"
                                >
                                    View Original Job
                                </flux:button>
                            </div>
                            @endif
                        </div>
                    </div>
                </flux:card>
                @endif

                <!-- Rating Metadata -->
                <flux:card>
                    <div class="p-4">
                        <flux:heading size="md" class="mb-4 text-green-600">
                            <flux:icon.cog-6-tooth class="mr-2" />
                            Rating Metadata
                        </flux:heading>
                        
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center space-x-2">
                                <flux:badge color="blue" size="sm">{{ $rating->model }}</flux:badge>
                                <flux:badge color="purple" size="sm">{{ $rating->rated_at->format('M j, Y') }}</flux:badge>
                            </div>

                            @if($rating->total_tokens)
                            <div class="grid grid-cols-2 gap-4 text-center">
                                <div class="bg-zinc-50 dark:bg-zinc-800 rounded p-2">
                                    <div class="text-xs text-zinc-500">Prompt Tokens</div>
                                    <div class="font-semibold text-purple-600">{{ number_format($rating->prompt_tokens) }}</div>
                                </div>
                                <div class="bg-zinc-50 dark:bg-zinc-800 rounded p-2">
                                    <div class="text-xs text-zinc-500">Response Tokens</div>
                                    <div class="font-semibold text-green-600">{{ number_format($rating->completion_tokens) }}</div>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4 text-center">
                                <div class="bg-zinc-50 dark:bg-zinc-800 rounded p-2">
                                    <div class="text-xs text-zinc-500">Total Tokens</div>
                                    <div class="font-semibold text-blue-600">{{ number_format($rating->total_tokens) }}</div>
                                </div>
                                @if($rating->cost)
                                <div class="bg-zinc-50 dark:bg-zinc-800 rounded p-2">
                                    <div class="text-xs text-zinc-500">Cost</div>
                                    <div class="font-semibold text-yellow-600">${{ number_format($rating->cost, 4) }}</div>
                                </div>
                                @endif
                            </div>
                            @endif

                            @if($rating->metadata && data_get($rating->metadata, 'profile_completeness'))
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded p-3">
                                <div class="text-xs text-blue-600 dark:text-blue-400 mb-1">Profile Completeness</div>
                                <div class="text-lg font-bold text-blue-700 dark:text-blue-300">
                                    {{ data_get($rating->metadata, 'profile_completeness') }}%
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </flux:card>

                <!-- AI Response Summary -->
                @if($parsedResponse)
                <flux:card>
                    <div class="p-4">
                        <flux:heading size="md" class="mb-4 text-orange-600">
                            <flux:icon.chart-bar class="mr-2" />
                            AI Scores
                        </flux:heading>
                        
                        @if(isset($parsedResponse['overall_score']))
                        <div class="mb-4">
                            <div class="text-center bg-gradient-to-r from-orange-50 to-yellow-50 dark:from-orange-900/20 dark:to-yellow-900/20 rounded-lg p-3">
                                <div class="text-xs text-orange-600 dark:text-orange-400">Overall Score</div>
                                <div class="text-2xl font-bold text-orange-700 dark:text-orange-300">
                                    {{ $parsedResponse['overall_score'] }}%
                                </div>
                            </div>
                        </div>
                        @endif

                        @if(isset($parsedResponse['scores']))
                        <div class="space-y-2 text-sm">
                            @foreach($parsedResponse['scores'] as $category => $score)
                            <div class="flex justify-between items-center">
                                <span class="capitalize text-zinc-600 dark:text-zinc-400">{{ str_replace('_', ' ', $category) }}:</span>
                                <flux:badge color="blue" size="sm">{{ $score }}%</flux:badge>
                            </div>
                            @endforeach
                        </div>
                        @endif

                        @if(isset($parsedResponse['confidence']))
                        <div class="mt-3 pt-3 border-t border-zinc-200 dark:border-zinc-700">
                            <div class="flex justify-between items-center">
                                <span class="text-zinc-600 dark:text-zinc-400">AI Confidence:</span>
                                <flux:badge color="green" size="sm">{{ $parsedResponse['confidence'] }}%</flux:badge>
                            </div>
                        </div>
                        @endif
                    </div>
                </flux:card>
                @endif
            </div>

            <!-- Right Column - Prompt & Response -->
            <div class="lg:col-span-2 space-y-6">
                <!-- AI Prompt -->
                <flux:card>
                    <div class="p-4">
                        <flux:heading size="md" class="mb-4 text-purple-600">
                            <flux:icon.chat-bubble-left-right class="mr-2" />
                            AI Prompt
                        </flux:heading>
                        
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 max-h-96 overflow-y-auto">
                            <pre class="text-xs text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap font-mono">{{ $rating->prompt }}</pre>
                        </div>
                    </div>
                </flux:card>

                <!-- AI Response -->
                <flux:card>
                    <div class="p-4">
                        <flux:heading size="md" class="mb-4 text-green-600">
                            <flux:icon.chat-bubble-oval-left class="mr-2" />
                            AI Response
                        </flux:heading>
                        
                        @if($parsedResponse)
                        <!-- Parsed JSON Response -->
                        <div class="space-y-4">
                            @if(isset($parsedResponse['reasoning']))
                            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                                <flux:heading size="sm" class="mb-3 text-green-700 dark:text-green-300">
                                    AI Reasoning:
                                </flux:heading>
                                <div class="space-y-2 text-sm">
                                    @foreach($parsedResponse['reasoning'] as $category => $reason)
                                    <div>
                                        <span class="font-medium text-green-700 dark:text-green-300 capitalize">
                                            {{ str_replace('_', ' ', $category) }}:
                                        </span>
                                        <span class="block text-green-600 dark:text-green-400 mt-1">{{ $reason }}</span>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            <!-- Raw JSON -->
                            <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                                <div class="text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-2">Raw JSON Response:</div>
                                <pre class="text-xs text-zinc-600 dark:text-zinc-400 whitespace-pre-wrap font-mono max-h-64 overflow-y-auto">{{ $this->formattedResponse }}</pre>
                            </div>
                        </div>
                        @else
                        <!-- Plain Text Response -->
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 max-h-96 overflow-y-auto">
                            <pre class="text-xs text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap font-mono">{{ $rating->response }}</pre>
                        </div>
                        @endif
                    </div>
                </flux:card>
            </div>
        </div>
    </div>
</div>
