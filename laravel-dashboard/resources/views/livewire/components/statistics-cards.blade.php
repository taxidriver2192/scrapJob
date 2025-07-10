<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    @foreach($cards as $card)
        <flux:card class="bg-{{ $card['color'] ?? 'blue' }}-600 text-white hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between p-6">
                <div>
                    <flux:heading size="2xl" class="text-white">{{ $card['value'] }}</flux:heading>
                    <p class="text-{{ $card['color'] ?? 'blue' }}-100">{{ $card['title'] }}</p>
                </div>
                <i class="fas fa-{{ $card['icon'] ?? 'chart-bar' }} text-4xl text-{{ $card['color'] ?? 'blue' }}-200"></i>
            </div>
        </flux:card>
    @endforeach
</div>
