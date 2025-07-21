<flux:card>
    <div class="p-4">
        <flux:heading size="md" class="mb-4 text-green-600">
            <flux:icon.cog-6-tooth class="mr-2" />Skills Required
        </flux:heading>
        
        @if($this->hasSkills())
            <div class="space-y-2">
                <flux:subheading class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 mb-3">
                    Required Skills & Technologies
                </flux:subheading>
                <div class="flex flex-wrap gap-2">
                    @foreach($this->getSkills() as $skill)
                        @if(!empty(trim($skill)))
                            <flux:badge
                                size="sm"
                                color="green"
                                variant="outline"
                                class="px-3 py-1 text-xs font-medium"
                            >
                                {{ trim($skill) }}
                            </flux:badge>
                        @endif
                    @endforeach
                </div>
                <div class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">
                    Total: {{ count(array_filter($this->getSkills(), fn($skill) => !empty(trim($skill)))) }} skills listed
                </div>
            </div>
        @else
            <div class="text-center py-6">
                <flux:icon.exclamation-triangle class="mx-auto h-8 w-8 text-zinc-400 mb-2" />
                <p class="text-zinc-500 dark:text-zinc-400 text-sm">No specific skills listed for this position.</p>
                <p class="text-zinc-400 dark:text-zinc-500 text-xs mt-1">Check the job description for skill requirements.</p>
            </div>
        @endif
    </div>
</flux:card>
