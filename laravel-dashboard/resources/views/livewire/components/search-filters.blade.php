<flux:card class="mb-8 bg-white dark:bg-zinc-900">
    <div class="p-6">
        <flux:heading size="lg" class="mb-4 text-zinc-900 dark:text-zinc-100">
            <i class="fas fa-search mr-2 text-zinc-600 dark:text-zinc-400"></i>{{ $title }}
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
            <!-- Global Search -->
            <div>
                <flux:label>Search</flux:label>
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search jobs, companies, descriptions..."
                    icon="search"
                />
            </div>

            <!-- Company Filter with Autocomplete -->
            <div>
                <flux:label>Company</flux:label>
                <flux:autocomplete wire:model.live="companyFilter" placeholder="Select or type company...">
                    @foreach($companies as $companyId => $companyName)
                        <flux:autocomplete.item value="{{ $companyName }}">
                            {{ $companyName }}
                        </flux:autocomplete.item>
                    @endforeach
                </flux:autocomplete>
            </div>

            <!-- Location Filter with Autocomplete -->
            <div>
                <flux:label>Location</flux:label>
                <flux:autocomplete wire:model.live="locationFilter" placeholder="Select or type location...">
                    @foreach($locations as $location)
                        <flux:autocomplete.item value="{{ $location }}">
                            {{ $location }}
                        </flux:autocomplete.item>
                    @endforeach
                </flux:autocomplete>
            </div>

            <!-- Items per page -->
            @if($showPerPage)
            <div>
                <flux:label>Items per page</flux:label>
                <flux:select wire:model.live="perPage">
                    <option value="5">5 per page</option>
                    <option value="10">10 per page</option>
                    <option value="20">20 per page</option>
                    <option value="50">50 per page</option>
                </flux:select>
            </div>
            @endif
        </div>

        @if($showDateFilters)
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Date From -->
            <div>
                <flux:label>Posted From</flux:label>
                <flux:input
                    type="date"
                    wire:model.live="dateFromFilter"
                />
            </div>

            <!-- Date To -->
            <div>
                <flux:label>Posted To</flux:label>
                <flux:input
                    type="date"
                    wire:model.live="dateToFilter"
                />
            </div>

            <!-- Clear Filters -->
            <div class="flex items-end">
                <flux:button wire:click="clearFilters" variant="outline" icon="x">
                    Clear Filters
                </flux:button>
            </div>
        </div>
        @else
        <div class="flex justify-end">
            <flux:button wire:click="clearFilters" variant="outline" icon="x">
                Clear Filters
            </flux:button>
        </div>
        @endif
    </div>
</flux:card>
