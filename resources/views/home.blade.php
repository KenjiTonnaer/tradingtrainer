<x-layouts.guest>
    <x-slot:title>Homepage - TradingTrainer</x-slot:title>

    <!-- Hero Section -->
    <div class="relative overflow-hidden bg-gradient-to-br from-purple-600 via-pink-500 to-orange-500 min-h-screen">
        <!-- Animated background elements -->
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-20 left-10 w-72 h-72 bg-white rounded-full mix-blend-overlay filter blur-3xl animate-pulse"></div>
            <div class="absolute bottom-20 right-10 w-96 h-96 bg-yellow-200 rounded-full mix-blend-overlay filter blur-3xl animate-pulse delay-700"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-32">
            <div class="text-center">
                <h1 class="text-5xl md:text-7xl font-extrabold text-white mb-6 leading-tight">
                    Master Your Trading Skills
                    <span class="block text-yellow-300 mt-2">Without Risk</span>
                </h1>
                <p class="text-xl md:text-2xl text-purple-100 mb-10 max-w-3xl mx-auto">
                    Practice your trading strategies with real-time market data and improve your skills before investing real money.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    <a href="{{ route('register') }}" class="group relative inline-flex items-center px-8 py-4 bg-white text-purple-600 text-lg font-bold rounded-full hover:bg-yellow-300 hover:text-purple-700 transition-all duration-300 transform hover:scale-105 shadow-2xl">
                        <span>Start Free Training</span>
                        <svg class="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                    <a href="#features" class="inline-flex items-center px-8 py-4 bg-transparent border-2 border-white text-white text-lg font-bold rounded-full hover:bg-white hover:text-purple-600 transition-all duration-300 transform hover:scale-105">
                        Explore More
                    </a>
                </div>

                <!-- Stats Section -->
                <div class="mt-20 grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                        <div class="text-4xl font-bold text-yellow-300 mb-2">10K+</div>
                        <div class="text-purple-100 font-medium">Active Traders</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                        <div class="text-4xl font-bold text-yellow-300 mb-2">1M+</div>
                        <div class="text-purple-100 font-medium">Simulated Trades</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                        <div class="text-4xl font-bold text-yellow-300 mb-2">95%</div>
                        <div class="text-purple-100 font-medium">Satisfaction</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Wave divider -->
        <div class="absolute bottom-0 left-0 right-0 -mb-px">
            <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg" class="block">
                <path d="M0 120L60 105C120 90 240 60 360 45C480 30 600 30 720 37.5C840 45 960 60 1080 67.5C1200 75 1320 75 1380 75L1440 75V120H1380C1320 120 1200 120 1080 120C960 120 840 120 720 120C600 120 480 120 360 120C240 120 120 120 60 120H0Z" fill="white"/>
            </svg>
        </div>
    </div>

    <!-- Features Section -->
    <div id="features" class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                    Why TradingTrainer?
                </h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    Everything you need to become a successful trader
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="group p-8 rounded-2xl bg-gradient-to-br from-purple-50 to-pink-50 shadow-[-8px_20px_40px_-8px_rgba(0,0,0,0.25)] hover:shadow-[-12px_30px_60px_-12px_rgba(0,0,0,0.35)] transition-all duration-300 transform hover:-translate-y-2 border-2 border-gray-200">
                    <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Real-time Data</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Train with real market data and learn how to react to real-time market moves.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="group p-8 rounded-2xl bg-gradient-to-br from-orange-50 to-yellow-50 shadow-[-8px_20px_40px_-8px_rgba(0,0,0,0.25)] hover:shadow-[-12px_30px_60px_-12px_rgba(0,0,0,0.35)] transition-all duration-300 transform hover:-translate-y-2 border-2 border-gray-200">
                    <div class="w-14 h-14 bg-gradient-to-br from-orange-500 to-yellow-500 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Practice Safely</h3>
                    <p class="text-gray-600 leading-relaxed">
                        No risks, no losses. Develop your strategies with virtual funds.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="group p-8 rounded-2xl bg-gradient-to-br from-pink-50 to-purple-50 shadow-[-8px_20px_40px_-8px_rgba(0,0,0,0.25)] hover:shadow-[-12px_30px_60px_-12px_rgba(0,0,0,0.35)] transition-all duration-300 transform hover:-translate-y-2 border-2 border-gray-200">
                    <div class="w-14 h-14 bg-gradient-to-br from-pink-500 to-purple-500 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Analytics Dashboard</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Analyze your performance and discover where to improve with detailed statistics.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="group p-8 rounded-2xl bg-gradient-to-br from-yellow-50 to-orange-50 shadow-[-8px_20px_40px_-8px_rgba(0,0,0,0.25)] hover:shadow-[-12px_30px_60px_-12px_rgba(0,0,0,0.35)] transition-all duration-300 transform hover:-translate-y-2 border-2 border-gray-200">
                    <div class="w-14 h-14 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Learn Strategies</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Access tutorials and lessons to expand your trading knowledge.
                    </p>
                </div>

                <!-- Feature 5 -->
                <div class="group p-8 rounded-2xl bg-gradient-to-br from-purple-50 to-orange-50 shadow-[-8px_20px_40px_-8px_rgba(0,0,0,0.25)] hover:shadow-[-12px_30px_60px_-12px_rgba(0,0,0,0.35)] transition-all duration-300 transform hover:-translate-y-2 border-2 border-gray-200">
                    <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-orange-500 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Community</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Learn from others, share experiences, and grow with a community of traders.
                    </p>
                </div>

                <!-- Feature 6 -->
                <div class="group p-8 rounded-2xl bg-gradient-to-br from-pink-50 to-yellow-50 shadow-[-8px_20px_40px_-8px_rgba(0,0,0,0.25)] hover:shadow-[-12px_30px_60px_-12px_rgba(0,0,0,0.35)] transition-all duration-300 transform hover:-translate-y-2 border-2 border-gray-200">
                    <div class="w-14 h-14 bg-gradient-to-br from-pink-500 to-yellow-500 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Available 24/7</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Practice whenever it suits you. Our platform is always available.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="relative py-24 bg-gradient-to-r from-purple-600 via-pink-500 to-orange-500 overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-1/4 w-96 h-96 bg-white rounded-full filter blur-3xl"></div>
            <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-yellow-200 rounded-full filter blur-3xl"></div>
        </div>

        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl md:text-5xl font-bold text-white mb-6">
                Ready to Get Started?
            </h2>
            <p class="text-xl text-purple-100 mb-10 max-w-2xl mx-auto">
                Join thousands of traders improving their skills with TradingTrainer. Start free today!
            </p>
            <a href="{{ route('register') }}" class="inline-flex items-center px-10 py-5 bg-white text-purple-600 text-xl font-bold rounded-full hover:bg-yellow-300 hover:text-purple-700 transition-all duration-300 transform hover:scale-110 shadow-2xl">
                <span>Start Your Trading Journey</span>
                <svg class="ml-3 w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </a>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <div class="flex items-center mb-4">
                        <svg class="h-8 w-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                        <span class="ml-2 text-xl font-bold text-white">TradingTrainer</span>
                    </div>
                    <p class="text-gray-400">
                        Master your trading skills without risk.
                    </p>
                </div>
                <div>
                    <h3 class="text-white font-bold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="hover:text-purple-400 transition">About Us</a></li>
                        <li><a href="#features" class="hover:text-purple-400 transition">Features</a></li>
                        <li><a href="#" class="hover:text-purple-400 transition">Pricing</a></li>
                        <li><a href="#" class="hover:text-purple-400 transition">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-white font-bold mb-4">Legal</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="hover:text-purple-400 transition">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-purple-400 transition">Terms of Service</a></li>
                        <li><a href="#" class="hover:text-purple-400 transition">Disclaimer</a></li>
                    </ul>
                </div>
            </div>
            <div class="mt-12 pt-8 border-t border-gray-800 text-center text-gray-400">
                <p>&copy; {{ date('Y') }} TradingTrainer. All rights reserved.</p>
            </div>
        </div>
    </footer>

</x-layouts.guest>
