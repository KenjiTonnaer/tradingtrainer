<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <style>
            /* Sidebar theming */
            .app-sidebar{width:260px;transition:width .2s ease,border-color .2s ease,background .2s ease,box-shadow .2s ease;background:linear-gradient(180deg,#ffffff 0%,#faf5ff 40%,#eef2ff 100%);overflow:hidden;position:relative;border-top-right-radius:16px !important;border-bottom-right-radius:16px !important;box-shadow:6px 0 24px -12px rgba(124,58,237,.18),0 8px 24px -16px rgba(236,72,153,.12),0 2px 4px rgba(16,24,40,.04)}
            .app-sidebar > *:first-child{overflow-x:hidden;overflow-y:auto;height:100%}
            .app-sidebar .brand-gradient{background:linear-gradient(135deg,#7c3aed,#ec4899 60%,#f97316);width:40px;height:40px;border-radius:10px}
            .app-sidebar .nav-item{border-radius:10px;padding:12px 14px;margin:2px 6px;color:#374151;display:flex;align-items:center;gap:14px;transition:background .15s ease,transform .15s ease}
            .app-sidebar .nav-item svg{transition:transform .15s ease}
            .app-sidebar .app-navlist .nav-item svg{width:40px;height:40px !important}
            .app-sidebar .nav-item .label{font-size:1rem;font-weight:600}
            .app-sidebar .nav-item:hover{background:rgba(124,58,237,.08);transform:translateX(2px)}
            .app-sidebar .nav-item:hover svg{transform:scale(1.05)}
            .app-sidebar .nav-item.active{background:linear-gradient(135deg,rgba(124,58,237,.12),rgba(236,72,153,.10));color:#7c3aed}
            .app-sidebar .icon-pill{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#7c3aed,#ec4899)}
            .app-sidebar .sidebar-header{row-gap:.4rem}
            .app-sidebar .toggle-row{display:flex;justify-content:flex-end;width:100%;padding-right:.5rem}
            body.sidebar-collapsed .app-sidebar .toggle-row{justify-content:flex-end;padding-right:.25rem}
            /* Account trigger hover nudge */
            .desktop-user{transition:transform .15s ease}
            .desktop-user:hover{transform:translateX(2px)}
            .mobile-user{transition:transform .15s ease}
            .mobile-user:hover{transform:translateX(2px)}
            .app-sidebar .icon-pill svg{color:#fff}

            /* Collapsed mode */
            body.sidebar-collapsed .app-sidebar{width:72px}
            body.sidebar-collapsed .app-sidebar{overflow-y:hidden}
            body.sidebar-collapsed .app-sidebar .label,
            body.sidebar-collapsed .app-sidebar .brand-text{display:none}
            /* Keep brand logo and collapse button same size collapsed/expanded */
            body.sidebar-collapsed .app-sidebar .brand-gradient{width:2.5rem;height:2.5rem}
            body.sidebar-collapsed #sidebar-collapse-toggle{width:40px;height:40px}
            body.sidebar-collapsed .app-sidebar .compact-center{justify-content:center}
            body.sidebar-collapsed .app-sidebar .nav-item{justify-content:center;gap:0;padding:12px}
            body.sidebar-collapsed .app-sidebar .app-navlist .nav-item svg{width:40px;height:40px !important}
            body.sidebar-collapsed .app-sidebar .sidebar-header{justify-content:center}
            /* Tooltip fallback using title attribute */

            /* Subtle scrollbar styling (only when expanded) */
            .app-sidebar::-webkit-scrollbar{width:8px}
            .app-sidebar::-webkit-scrollbar-thumb{background:#e9d5ff;border-radius:10px}
            .app-sidebar::-webkit-scrollbar-track{background:transparent}
        </style>
    </head>
    <body class="min-h-screen bg-gradient-to-br from-gray-50 to-purple-50">
        <flux:sidebar sticky stashable class="bg-white shadow-xl rounded-r-2xl app-sidebar">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <!-- Gradient right border (thicker, dark to light purple/pink, follows rounded corners) -->
            <div aria-hidden="true" class="pointer-events-none absolute inset-y-0 right-0 w-[8px] rounded-r-2xl bg-gradient-to-b from-purple-600 via-fuchsia-400 to-pink-300 opacity-100"></div>

            <div class="px-2 pt-2 pb-2 sidebar-header flex flex-col gap-2 w-full items-center">
                <a href="{{ route('dashboard') }}" class="flex items-center justify-center space-x-2 rtl:space-x-reverse group" wire:navigate title="Dashboard">
                    <div class="w-10 h-10 brand-gradient rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <span class="text-lg font-bold bg-gradient-to-r from-purple-600 via-pink-500 to-orange-500 bg-clip-text text-transparent brand-text">TradingTrainer</span>
                </a>
                <div class="toggle-row">
                    <button id="sidebar-collapse-toggle" type="button" class="icon-pill shadow ring-1 ring-purple-200 hover:ring-purple-300 transition" title="Toggle sidebar">
                        <svg id="sidebar-collapse-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                            <path fill-rule="evenodd" d="M15.78 7.72a.75.75 0 010 1.06L12.56 12l3.22 3.22a.75.75 0 11-1.06 1.06l-3.75-3.75a.75.75 0 010-1.06l3.75-3.75a.75.75 0 011.06 0z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>

            <flux:navlist variant="outline" class="app-navlist">
                <flux:navlist.group class="grid group-heading">
                    <flux:navlist.item icon="home" :href="route('home')" wire:navigate title="Homepage" class="nav-item {{ request()->routeIs('home') ? 'active' : '' }}">
                        <span class="label">Homepage</span>
                    </flux:navlist.item>
                    <flux:navlist.item icon="chart-bar" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate title="Dashboard" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <span class="label">Dashboard</span>
                    </flux:navlist.item>
                </flux:navlist.group>

                <flux:navlist.group class="grid group-heading">
                    <flux:navlist.item icon="arrow-trending-up" :href="route('markets.show', ['symbol' => 'AAPL'])" wire:navigate title="Stocks Trading" class="nav-item {{ request()->routeIs('markets.show') && !in_array(request()->route('symbol'), ['BTC', 'ETH', 'SOL', 'DOGE', 'XRP', 'ADA']) ? 'active' : '' }}">
                        <span class="label">Stocks</span>
                    </flux:navlist.item>
                    <flux:navlist.item icon="currency-dollar" :href="route('markets.show', ['symbol' => 'BTC'])" wire:navigate title="Crypto Trading" class="nav-item {{ request()->routeIs('markets.show') && in_array(request()->route('symbol'), ['BTC', 'ETH', 'SOL', 'DOGE', 'XRP', 'ADA']) ? 'active' : '' }}">
                        <span class="label">Crypto</span>
                    </flux:navlist.item>
                </flux:navlist.group>
            </flux:navlist>

            <flux:spacer />
            <!-- Desktop User Menu -->
            <flux:dropdown class="hidden lg:block desktop-user" position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon:trailing="chevrons-up-down"
                    data-test="sidebar-menu-button"
                />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>Settings</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full" data-test="logout-button">
                            Log Out
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown class="mobile-user" position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>Settings</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full" data-test="logout-button">
                            Log Out
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
        <script>
            (function(){
                try{
                    const body= document.body;
                    const key='tt.sidebar.collapsed';
                    const btn=document.getElementById('sidebar-collapse-toggle');
                    const icon=document.getElementById('sidebar-collapse-icon');
                    const apply=(v)=>{
                        if(v){ body.classList.add('sidebar-collapsed'); icon.style.transform='rotate(180deg)'; }
                        else { body.classList.remove('sidebar-collapsed'); icon.style.transform='rotate(0deg)'; }
                    };
                    let collapsed = localStorage.getItem(key)==='1';
                    apply(collapsed);
                    if(btn){
                        btn.addEventListener('click',()=>{
                            collapsed=!collapsed;
                            localStorage.setItem(key, collapsed?'1':'0');
                            apply(collapsed);
                        });
                    }
                }catch(e){console.warn('Sidebar collapse init failed', e)}
            })();
        </script>
    </body>
</html>
