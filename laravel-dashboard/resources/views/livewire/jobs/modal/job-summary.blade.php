<flux:card>
    <div class="p-4">
        <flux:heading size="md" class="mb-4 text-orange-600">
            <flux:icon.document-magnifying-glass class="mr-2" />Job Summary
        </flux:heading>
        
        @if($this->hasSummary())
            <div class="space-y-3">
                <flux:subheading class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">
                    Brief Overview
                </flux:subheading>
                <div class="bg-orange-50 dark:bg-orange-900/20 border-l-4 border-orange-400 p-4 rounded-r-lg">
                    <div class="prose prose-sm max-w-none text-zinc-700 dark:text-zinc-300">
                        <div class="formatted-summary">{!! nl2br(strip_tags($this->getBriefSummary(), '<p><br><strong><b><em><i><ul><ol><li><span>')) !!}</div>
                    </div>
                </div>
                <div class="flex justify-between items-center mt-3 text-xs text-zinc-500 dark:text-zinc-400">
                    <span class="flex items-center">
                        <flux:icon.document-text class="mr-1 size-3" />
                        {{ $this->getSummaryWordCount() }} words
                    </span>
                    <flux:badge size="sm" color="orange" variant="outline">
                        AI Generated Summary
                    </flux:badge>
                </div>
            </div>
        @else
            <div class="text-center py-6">
                <flux:icon.document-duplicate class="mx-auto h-8 w-8 text-zinc-400 mb-2" />
                <p class="text-zinc-500 dark:text-zinc-400 text-sm">No summary available for this position.</p>
                <p class="text-zinc-400 dark:text-zinc-500 text-xs mt-1">The full job description is available below.</p>
            </div>
        @endif
    </div>
</flux:card>
