<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? config('app.name', 'Battle Line') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|bricolage-grotesque:500,600,700,800" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen">
        <div class="relative isolate overflow-hidden">
            <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-96 bg-[radial-gradient(circle_at_top,rgba(244,145,56,0.22),transparent_52%)]"></div>
            <div class="pointer-events-none absolute inset-x-0 bottom-0 -z-10 h-80 bg-[radial-gradient(circle_at_bottom_right,rgba(245,200,98,0.14),transparent_50%)]"></div>
            <div class="mx-auto w-full max-w-7xl px-5 pt-5 sm:px-8 lg:px-10">
                <div class="relative z-40 flex flex-wrap items-center justify-between gap-4 overflow-visible rounded-full border border-white/10 bg-black/20 px-5 py-4 shadow-xl shadow-black/20 backdrop-blur-md">
                    <a href="{{ route('battle-line-games.page.index') }}" class="inline-flex items-center rounded-full border border-white/10 bg-white/[0.04] px-5 py-2.5 text-sm font-semibold uppercase tracking-[0.22em] text-white/72 transition hover:bg-white/[0.08] hover:text-white">
                        Command Hall
                    </a>

                    @isset($topbar)
                        <div class="flex min-w-0 flex-1 justify-center">
                            {{ $topbar }}
                        </div>
                    @endisset

                    @auth
                        <details data-account-menu class="group relative z-10 [&[open]]:z-50">
                            <summary class="flex list-none cursor-pointer items-center gap-3 rounded-full border border-white/10 bg-white/[0.05] px-4 py-2 text-sm text-white/75 transition hover:bg-white/[0.08] [&::-webkit-details-marker]:hidden">
                                <span class="font-semibold">Commander {{ auth()->user()->name }}</span>
                                <svg class="size-4 transition group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.51a.75.75 0 0 1-1.08 0l-4.25-4.51a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                </svg>
                            </summary>

                            <div class="pointer-events-none absolute right-0 top-[calc(100%+0.75rem)] z-50 w-60 origin-top-right translate-y-2 scale-[0.98] rounded-[1.6rem] border border-white/10 bg-stone-950/95 p-3 opacity-0 shadow-2xl shadow-black/40 backdrop-blur-md transition duration-200 ease-out group-open:pointer-events-auto group-open:translate-y-0 group-open:scale-100 group-open:opacity-100">
                                <div class="rounded-[1.2rem] border border-white/8 bg-white/[0.03] px-4 py-3">
                                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-white/40">Commander</p>
                                    <p class="mt-1 font-display text-xl text-white">{{ auth()->user()->name }}</p>
                                </div>

                                <form action="{{ route('logout') }}" method="post" class="mt-3">
                                    @csrf
                                    <button type="submit" class="w-full rounded-[1.2rem] border border-white/10 bg-white/[0.04] px-4 py-3 text-sm font-semibold text-white/72 transition hover:bg-white/[0.08] hover:text-white">
                                        Sign out
                                    </button>
                                </form>
                            </div>
                        </details>
                    @else
                        <div class="flex flex-wrap items-center gap-3">
                            <a href="{{ route('login') }}" class="rounded-full border border-white/10 bg-white/[0.04] px-4 py-2 text-sm font-semibold text-white/70 transition hover:bg-white/[0.08]">
                                Sign in
                            </a>
                            <a href="{{ route('register') }}" class="rounded-full border border-war-gold/40 bg-war-gold/15 px-4 py-2 text-sm font-semibold text-war-gold transition hover:bg-war-gold/25">
                                Register
                            </a>
                        </div>
                    @endauth
                </div>
            </div>
            {{ $slot }}
        </div>
    </body>
</html>
