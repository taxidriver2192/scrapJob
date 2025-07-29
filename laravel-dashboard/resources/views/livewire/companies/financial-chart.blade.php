<div>
    @if($showChart && count($financialData) > 0)
        <flux:card>
            <div class="p-4">
                <flux:heading size="md" class="mb-6 text-purple-600">
                    <flux:icon.chart-bar class="mr-2" />
                    Financial Performance
                </flux:heading>

                <!-- Financial Data Table - Always show when we have data -->
                <div class="mb-6">
                    <flux:text class="text-lg font-semibold text-zinc-700 dark:text-zinc-300 mb-4 block">Financial Data</flux:text>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                    <th class="text-left py-2 px-4 font-semibold text-zinc-700 dark:text-zinc-300">Year</th>
                                    <th class="text-right py-2 px-4 font-semibold text-zinc-700 dark:text-zinc-300">Gross Profit</th>
                                    <th class="text-right py-2 px-4 font-semibold text-zinc-700 dark:text-zinc-300">Total Equity</th>
                                    <th class="text-right py-2 px-4 font-semibold text-zinc-700 dark:text-zinc-300">Balance Sheet</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($financialData as $yearData)
                                <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                    <td class="py-3 px-4 font-medium">{{ $yearData['year'] }}</td>
                                    <td class="py-3 px-4 text-right">
                                        <span class="{{ $yearData['gross_profit'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ number_format($yearData['gross_profit'] / 1000, 0) }}K
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <span class="{{ $yearData['total_equity'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ number_format($yearData['total_equity'] / 1000, 0) }}K
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <span class="{{ $yearData['balance_sheet_status'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ number_format($yearData['balance_sheet_status'] / 1000, 0) }}K
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Year-over-year comparison for 2 years -->
                    @if(count($financialData) == 2)
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                        @php
                            $older = $financialData[0];
                            $newer = $financialData[1];
                            $grossChange = $newer['gross_profit'] - $older['gross_profit'];
                            $equityChange = $newer['total_equity'] - $older['total_equity'];
                            $balanceChange = $newer['balance_sheet_status'] - $older['balance_sheet_status'];
                        @endphp

                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 p-4 rounded-lg">
                            <flux:text class="text-sm font-semibold text-blue-700 dark:text-blue-300 mb-2">Gross Profit Change</flux:text>
                            <div class="flex items-center">
                                @if($grossChange >= 0)
                                    <flux:icon.arrow-trending-up class="size-5 text-green-500 mr-2" />
                                    <span class="text-lg font-bold text-green-600 dark:text-green-400">+{{ number_format($grossChange / 1000, 0) }}K</span>
                                @else
                                    <flux:icon.arrow-trending-down class="size-5 text-red-500 mr-2" />
                                    <span class="text-lg font-bold text-red-600 dark:text-red-400">{{ number_format($grossChange / 1000, 0) }}K</span>
                                @endif
                            </div>
                            <flux:text class="text-xs text-zinc-600 dark:text-zinc-400">{{ $older['year'] }} to {{ $newer['year'] }}</flux:text>
                        </div>

                        <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 p-4 rounded-lg">
                            <flux:text class="text-sm font-semibold text-green-700 dark:text-green-300 mb-2">Equity Change</flux:text>
                            <div class="flex items-center">
                                @if($equityChange >= 0)
                                    <flux:icon.arrow-trending-up class="size-5 text-green-500 mr-2" />
                                    <span class="text-lg font-bold text-green-600 dark:text-green-400">+{{ number_format($equityChange / 1000, 0) }}K</span>
                                @else
                                    <flux:icon.arrow-trending-down class="size-5 text-red-500 mr-2" />
                                    <span class="text-lg font-bold text-red-600 dark:text-red-400">{{ number_format($equityChange / 1000, 0) }}K</span>
                                @endif
                            </div>
                            <flux:text class="text-xs text-zinc-600 dark:text-zinc-400">{{ $older['year'] }} to {{ $newer['year'] }}</flux:text>
                        </div>

                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 p-4 rounded-lg">
                            <flux:text class="text-sm font-semibold text-purple-700 dark:text-purple-300 mb-2">Balance Change</flux:text>
                            <div class="flex items-center">
                                @if($balanceChange >= 0)
                                    <flux:icon.arrow-trending-up class="size-5 text-green-500 mr-2" />
                                    <span class="text-lg font-bold text-green-600 dark:text-green-400">+{{ number_format($balanceChange / 1000, 0) }}K</span>
                                @else
                                    <flux:icon.arrow-trending-down class="size-5 text-red-500 mr-2" />
                                    <span class="text-lg font-bold text-red-600 dark:text-red-400">{{ number_format($balanceChange / 1000, 0) }}K</span>
                                @endif
                            </div>
                            <flux:text class="text-xs text-zinc-600 dark:text-zinc-400">{{ $older['year'] }} to {{ $newer['year'] }}</flux:text>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Multi-Year Trend Analysis and Chart - Only show for 3+ years -->
                @if(count($financialData) >= 3)
                <div class="mt-8 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:text class="text-lg font-semibold text-zinc-700 dark:text-zinc-300 mb-4 block">Performance Trends</flux:text>

                    <!-- Trend Summary Cards -->
                    @if($this->trendAnalysis)
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 p-4 rounded-lg">
                            <flux:text class="text-sm font-semibold text-blue-700 dark:text-blue-300 mb-2">Gross Profit Trend</flux:text>
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="flex items-center">
                                        @if($this->trendAnalysis['grossProfitTrend'] >= 0)
                                            <flux:icon.arrow-trending-up class="size-5 text-green-500 mr-2" />
                                            <span class="text-lg font-bold text-green-600 dark:text-green-400">+{{ number_format(abs($this->trendAnalysis['grossProfitTrend']), 0) }}%</span>
                                        @else
                                            <flux:icon.arrow-trending-down class="size-5 text-red-500 mr-2" />
                                            <span class="text-lg font-bold text-red-600 dark:text-red-400">{{ number_format($this->trendAnalysis['grossProfitTrend'], 0) }}%</span>
                                        @endif
                                    </div>
                                    <flux:text class="text-xs text-zinc-600 dark:text-zinc-400">{{ $this->trendAnalysis['yearSpan'] }}-year change</flux:text>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-medium text-blue-600 dark:text-blue-400">{{ number_format(abs($this->trendAnalysis['grossProfitCAGR']), 1) }}%</div>
                                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">Annual avg</flux:text>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 p-4 rounded-lg">
                            <flux:text class="text-sm font-semibold text-green-700 dark:text-green-300 mb-2">Total Equity Trend</flux:text>
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="flex items-center">
                                        @if($this->trendAnalysis['equityTrend'] >= 0)
                                            <flux:icon.arrow-trending-up class="size-5 text-green-500 mr-2" />
                                            <span class="text-lg font-bold text-green-600 dark:text-green-400">+{{ number_format(abs($this->trendAnalysis['equityTrend']), 0) }}%</span>
                                        @else
                                            <flux:icon.arrow-trending-down class="size-5 text-red-500 mr-2" />
                                            <span class="text-lg font-bold text-red-600 dark:text-red-400">{{ number_format($this->trendAnalysis['equityTrend'], 0) }}%</span>
                                        @endif
                                    </div>
                                    <flux:text class="text-xs text-zinc-600 dark:text-zinc-400">{{ $this->trendAnalysis['yearSpan'] }}-year change</flux:text>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-medium text-green-600 dark:text-green-400">{{ number_format(abs($this->trendAnalysis['equityCAGR']), 1) }}%</div>
                                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">Annual avg</flux:text>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 p-4 rounded-lg">
                            <flux:text class="text-sm font-semibold text-purple-700 dark:text-purple-300 mb-2">Balance Sheet Trend</flux:text>
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="flex items-center">
                                        @if($this->trendAnalysis['balanceTrend'] >= 0)
                                            <flux:icon.arrow-trending-up class="size-5 text-green-500 mr-2" />
                                            <span class="text-lg font-bold text-green-600 dark:text-green-400">+{{ number_format(abs($this->trendAnalysis['balanceTrend']), 0) }}%</span>
                                        @else
                                            <flux:icon.arrow-trending-down class="size-5 text-red-500 mr-2" />
                                            <span class="text-lg font-bold text-red-600 dark:text-red-400">{{ number_format($this->trendAnalysis['balanceTrend'], 0) }}%</span>
                                        @endif
                                    </div>
                                    <flux:text class="text-xs text-zinc-600 dark:text-zinc-400">{{ $this->trendAnalysis['yearSpan'] }}-year change</flux:text>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-medium text-purple-600 dark:text-purple-400">{{ number_format(abs($this->trendAnalysis['balanceCAGR']), 1) }}%</div>
                                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">Annual avg</flux:text>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Multi-Year Line Chart -->
                    <flux:chart :value="$this->enhancedFinancialData" class="aspect-[4/1]">
                        <flux:chart.viewport class="min-h-[24rem]">
                            <flux:chart.svg>
                                <!-- Gross Profit Line with Area -->
                                <flux:chart.area field="gross_profit" class="text-blue-200/30 dark:text-blue-400/20" />
                                <flux:chart.line field="gross_profit" class="text-blue-600 dark:text-blue-400" stroke-width="3" />
                                <flux:chart.point field="gross_profit" class="text-blue-600 dark:text-blue-400" r="5" stroke-width="2" />

                                <!-- Total Equity Line with Area -->
                                <flux:chart.area field="total_equity" class="text-green-200/30 dark:text-green-400/20" />
                                <flux:chart.line field="total_equity" class="text-green-600 dark:text-green-400" stroke-width="3" />
                                <flux:chart.point field="total_equity" class="text-green-600 dark:text-green-400" r="5" stroke-width="2" />

                                <!-- Balance Sheet Line with Area -->
                                <flux:chart.area field="balance_sheet_status" class="text-purple-200/30 dark:text-purple-400/20" />
                                <flux:chart.line field="balance_sheet_status" class="text-purple-600 dark:text-purple-400" stroke-width="3" />
                                <flux:chart.point field="balance_sheet_status" class="text-purple-600 dark:text-purple-400" r="5" stroke-width="2" />

                                <!-- X Axis (Years) -->
                                <flux:chart.axis axis="x" field="year">
                                    <flux:chart.axis.tick />
                                    <flux:chart.axis.line />
                                    <flux:chart.axis.grid class="text-zinc-200 dark:text-zinc-700" />
                                </flux:chart.axis>

                                <!-- Y Axis (Currency) -->
                                <flux:chart.axis axis="y" position="left" :format="[
                                    'notation' => 'compact',
                                    'compactDisplay' => 'short',
                                    'maximumFractionDigits' => 1,
                                ]">
                                    <flux:chart.axis.grid class="text-zinc-200 dark:text-zinc-700" />
                                    <flux:chart.axis.tick />
                                </flux:chart.axis>

                                <flux:chart.cursor />
                            </flux:chart.svg>
                        </flux:chart.viewport>

                        <!-- Enhanced Tooltip with Year-over-Year Changes -->
                        <flux:chart.tooltip>
                            <flux:chart.tooltip.heading field="year" />

                            <!-- Gross Profit -->
                            <flux:chart.tooltip.value field="gross_profit" label="Gross Profit" :format="[
                                'notation' => 'compact',
                                'compactDisplay' => 'short',
                                'maximumFractionDigits' => 1,
                            ]" />
                            <flux:chart.tooltip.value field="gross_profit_change_display" label="vs Previous Year" />

                            <!-- Total Equity -->
                            <flux:chart.tooltip.value field="total_equity" label="Total Equity" :format="[
                                'notation' => 'compact',
                                'compactDisplay' => 'short',
                                'maximumFractionDigits' => 1,
                            ]" />
                            <flux:chart.tooltip.value field="total_equity_change_display" label="vs Previous Year" />

                            <!-- Balance Sheet -->
                            <flux:chart.tooltip.value field="balance_sheet_status" label="Balance Sheet" :format="[
                                'notation' => 'compact',
                                'compactDisplay' => 'short',
                                'maximumFractionDigits' => 1,
                            ]" />
                            <flux:chart.tooltip.value field="balance_sheet_change_display" label="vs Previous Year" />
                        </flux:chart.tooltip>
                    </flux:chart>
                </div>
                @endif
            </div>
        </flux:card>
    @else
        <flux:card>
            <div class="p-4">
                <flux:heading size="md" class="mb-4 text-purple-600">
                    <flux:icon.chart-bar class="mr-2" />
                    Financial Summary
                </flux:heading>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 italic">No financial information available.</p>
            </div>
        </flux:card>
    @endif
</div>
