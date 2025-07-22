<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    @foreach ($cards as $card)
        <flux:card
            class="bg-blue-600 dark:bg-blue-400
                   dark:text-zinc-100
                   hover:shadow-lg dark:hover:shadow-lg
                   transition-shadow">
            <div class="flex items-center justify-between p-6">
                <div>
                    <flux:heading size="2xl"
                                  class="dark:text-zinc-100">
                        {{ $card['value'] }}
                    </flux:heading>

                    <flux:text
                        class="text-blue-100 dark:text-blue-300">
                        {{ $card['title'] }}
                    </flux:text>
                </div>

                <flux:icon
                    name="{{ $card['icon'] ?? 'chart-bar' }}"
                    class="text-4xl text-blue-200 dark:text-blue-400" />
            </div>
        </flux:card>
    @endforeach
</div>
