<x-layouts.app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
        <!-- Welcome Header -->
        <div class="bg-gradient-to-r from-purple-600 via-pink-500 to-orange-500 rounded-2xl p-8 text-white shadow-xl">
            <h1 class="text-3xl font-bold mb-2">Welcome back, {{ auth()->user()->name }}! 👋</h1>
            <p class="text-purple-100">Here’s an overview of your trading performance</p>
        </div>

        <!-- Stats Grid -->
        <div class="grid gap-6 md:grid-cols-3">
            <!-- Portfolio Value -->
            <div class="group relative overflow-hidden bg-white rounded-2xl p-6 shadow-lg hover:shadow-2xl transition-all duration-300 border border-purple-100 hover:-translate-y-1">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Wallet Balance</p>
                        <h3 class="text-3xl font-bold text-gray-900">{{ auth()->user()->wallet?->formatted_balance ?? '€ 0,00' }}</h3>
                        <p class="text-sm text-gray-500 mt-2">{{ auth()->user()->wallet?->currency ?? 'EUR' }}</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total Trades -->
            <div class="group relative overflow-hidden bg-white rounded-2xl p-6 shadow-lg hover:shadow-2xl transition-all duration-300 border border-orange-100 hover:-translate-y-1">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Total Trades</p>
                        <h3 class="text-3xl font-bold text-gray-900">247</h3>
                        <p class="text-sm text-gray-500 mt-2">152 profitable</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-yellow-500 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Win Rate -->
            <div class="group relative overflow-hidden bg-white rounded-2xl p-6 shadow-lg hover:shadow-2xl transition-all duration-300 border border-pink-100 hover:-translate-y-1">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Win Rate</p>
                        <h3 class="text-3xl font-bold text-gray-900">61.5%</h3>
                        <p class="text-sm text-purple-600 font-semibold mt-2">Excellent! 🎯</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-pink-500 to-purple-500 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart Area -->
        <div class="grid gap-6 lg:grid-cols-2">
            <!-- Performance Chart -->
            <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-200">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-900">Portfolio Performance</h2>
                    <div class="flex gap-2">
                        <button class="px-3 py-1 text-sm font-medium text-purple-600 bg-purple-50 rounded-lg hover:bg-purple-100 transition">1M</button>
                        <button class="px-3 py-1 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-lg transition">3M</button>
                        <button class="px-3 py-1 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-lg transition">1Y</button>
                    </div>
                </div>
                <div class="relative h-64 flex items-center justify-center border-2 border-dashed border-gray-200 rounded-xl">
                    <p class="text-gray-400">Chart goes here (integrate with Chart.js or ApexCharts)</p>
                </div>
            </div>

            <!-- Recent Trades -->
            <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-200">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Recent Trades</h2>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl border border-green-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center text-white font-bold">BTC</div>
                            <div>
                                <p class="font-semibold text-gray-900">Bitcoin</p>
                                <p class="text-sm text-gray-600">Buy • 0.05 BTC</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-green-600">+€234.50</p>
                            <p class="text-sm text-gray-600">2 hours ago</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-gradient-to-r from-red-50 to-pink-50 rounded-xl border border-red-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center text-white font-bold">ETH</div>
                            <div>
                                <p class="font-semibold text-gray-900">Ethereum</p>
                                <p class="text-sm text-gray-600">Sell • 1.2 ETH</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-red-600">-€89.20</p>
                            <p class="text-sm text-gray-600">5 hours ago</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl border border-green-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center text-white font-bold">SOL</div>
                            <div>
                                <p class="font-semibold text-gray-900">Solana</p>
                                <p class="text-sm text-gray-600">Buy • 10 SOL</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-green-600">+€156.80</p>
                            <p class="text-sm text-gray-600">1 day ago</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-2xl p-6 border border-purple-200">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Quick Actions</h2>
            <div class="grid gap-4 md:grid-cols-4">
                <button class="flex items-center gap-3 p-4 bg-white rounded-xl shadow hover:shadow-lg transition-all hover:-translate-y-1 group">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    <span class="font-semibold text-gray-900">New Trade</span>
                </button>
                <button class="flex items-center gap-3 p-4 bg-white rounded-xl shadow hover:shadow-lg transition-all hover:-translate-y-1 group">
                    <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-yellow-500 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <span class="font-semibold text-gray-900">Analytics</span>
                </button>
                <button class="flex items-center gap-3 p-4 bg-white rounded-xl shadow hover:shadow-lg transition-all hover:-translate-y-1 group">
                    <div class="w-10 h-10 bg-gradient-to-br from-pink-500 to-purple-500 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <span class="font-semibold text-gray-900">Lessons</span>
                </button>
                <button class="flex items-center gap-3 p-4 bg-white rounded-xl shadow hover:shadow-lg transition-all hover:-translate-y-1 group">
                    <div class="w-10 h-10 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <span class="font-semibold text-gray-900">Community</span>
                </button>
            </div>
        </div>
    </div>
</x-layouts.app>
