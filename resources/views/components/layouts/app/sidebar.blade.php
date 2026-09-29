<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <style>
            /* Sidebar theming */
            .app-sidebar{
                width:272px;
                transition:width .25s ease,border-color .2s ease,background .2s ease,box-shadow .2s ease;
                background:linear-gradient(180deg,#0f172a 0%,#111827 58%,#0b1220 100%) !important;
                overflow:hidden;
                position:relative;
                border:1px solid rgba(71,85,105,.6);
                border-left:none;
                border-top-right-radius:20px !important;
                border-bottom-right-radius:20px !important;
                box-shadow:12px 0 32px -20px rgba(15,23,42,.7),0 12px 30px -20px rgba(20,184,166,.24);
            }
            .app-sidebar > *:first-child{overflow-x:hidden;overflow-y:auto;height:100%}
            .app-sidebar .brand-gradient{background:linear-gradient(135deg,#14b8a6,#0ea5e9);width:42px;height:42px;border-radius:13px;box-shadow:0 12px 26px -12px rgba(20,184,166,.85)}
            .app-sidebar .nav-item{border-radius:11px;padding:11px 14px;margin:3px 8px;color:#cbd5e1;display:flex;align-items:center;gap:13px;transition:background .18s ease,border-color .18s ease,color .18s ease,transform .18s ease}
            .app-sidebar .nav-item svg{transition:transform .15s ease,color:inherit}
            .app-sidebar .app-navlist .nav-item svg{width:24px;height:24px !important;color:#94a3b8}
            .app-sidebar .nav-item .label{font-size:0.92rem;font-weight:600;letter-spacing:.01em}
            .app-sidebar .nav-item:hover{background:rgba(30,41,59,.85);transform:translateX(3px);color:#f8fafc}
            .app-sidebar .nav-item:hover svg{transform:scale(1.04)}
            .app-sidebar .nav-item.active{background:linear-gradient(90deg,rgba(20,184,166,.2),rgba(14,165,233,.08));color:#5eead4;border:1px solid rgba(45,212,191,.28);box-shadow:inset 3px 0 0 #2dd4bf,0 10px 24px -18px rgba(20,184,166,.8)}
            .app-sidebar .nav-item.active svg{color:#5eead4}
            .app-sidebar .icon-pill{width:40px;height:40px;border-radius:11px;display:flex;align-items:center;justify-content:center;background:#1e293b;box-shadow:inset 0 0 0 1px rgba(148,163,184,.22),0 10px 18px -12px rgba(15,23,42,.9)}
            .app-sidebar .sidebar-header{row-gap:.5rem}
            .app-sidebar .toggle-row{display:flex;justify-content:flex-end;width:100%;padding-right:.5rem}
            body.sidebar-collapsed .app-sidebar .toggle-row{justify-content:flex-end;padding-right:.25rem}
            .desktop-user,.mobile-user{transition:transform .15s ease}
            .desktop-user:hover,.mobile-user:hover{transform:translateX(2px)}
            .app-sidebar .icon-pill svg{color:#fff}
            .app-sidebar .group-heading{border-top:1px solid rgba(71,85,105,.35);padding-top:.75rem;margin-top:.75rem}
            .app-sidebar .group-heading:first-child{border-top:0;padding-top:0;margin-top:0}
            .app-sidebar [data-flux-profile]{color:#e2e8f0}

            body.sidebar-collapsed .app-sidebar{width:76px}
            body.sidebar-collapsed .app-sidebar{overflow-y:hidden}
            body.sidebar-collapsed .app-sidebar .label,
            body.sidebar-collapsed .app-sidebar .brand-text{display:none}
            body.sidebar-collapsed .app-sidebar .brand-gradient{width:2.5rem;height:2.5rem}
            body.sidebar-collapsed #sidebar-collapse-toggle{width:40px;height:40px}
            body.sidebar-collapsed .app-sidebar .compact-center{justify-content:center}
            body.sidebar-collapsed .app-sidebar .nav-item{justify-content:center;gap:0;padding:12px}
            body.sidebar-collapsed .app-sidebar .app-navlist .nav-item svg{width:24px;height:24px !important}
            body.sidebar-collapsed .app-sidebar .sidebar-header{justify-content:center}

            .app-sidebar::-webkit-scrollbar{width:8px}
            .app-sidebar::-webkit-scrollbar-thumb{background:linear-gradient(180deg,#2dd4bf,#0ea5e9);border-radius:10px}
            .app-sidebar::-webkit-scrollbar-track{background:transparent}
        </style>
    </head>
    <body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
        <flux:sidebar sticky stashable class="bg-white/85 shadow-[0_20px_45px_-28px_rgba(124,58,237,0.35)] rounded-r-2xl app-sidebar backdrop-blur-sm">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <!-- Gradient right border (thicker, dark to light purple/pink, follows rounded corners) -->
            <div aria-hidden="true" class="pointer-events-none absolute inset-y-0 right-0 w-[8px] rounded-r-2xl bg-gradient-to-b from-teal-400 via-cyan-400 to-sky-500 opacity-100"></div>

            <div class="px-2 pt-2 pb-2 sidebar-header flex flex-col gap-2 w-full items-center">
                <a href="{{ route('dashboard') }}" class="flex items-center justify-center space-x-2 rtl:space-x-reverse group" wire:navigate title="Dashboard">
                    <div class="w-10 h-10 brand-gradient rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 17l6-6 4 4 8-8"/>
                            <path d="M14 7h7v7"/>
                        </svg>
                    </div>
                    <span class="text-lg font-bold bg-gradient-to-r from-teal-300 via-cyan-300 to-sky-400 bg-clip-text text-transparent brand-text">TradingTrainer</span>
                </a>
                <div class="toggle-row">
                    <button id="sidebar-collapse-toggle" type="button" class="icon-pill shadow ring-1 ring-teal-300/40 hover:ring-cyan-300/60 transition" title="Toggle sidebar">
                        <svg id="sidebar-collapse-icon" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M15 18l-6-6 6-6"/>
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
