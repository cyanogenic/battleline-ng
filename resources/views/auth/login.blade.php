<x-layouts.battle-line :title="'Sign In'">
    <main class="mx-auto flex min-h-[calc(100vh-6rem)] w-full max-w-6xl items-center px-5 py-10 sm:px-8 lg:px-10">
        <div class="grid w-full gap-8 lg:grid-cols-[1.05fr_0.95fr]">
            <section class="space-y-6 rounded-[2rem] border border-white/10 bg-white/[0.04] p-7 shadow-2xl shadow-black/20 backdrop-blur-sm sm:p-9">
                <p class="text-sm font-semibold uppercase tracking-[0.3em] text-war-gold/75">Return To The Front</p>
                <div class="space-y-4">
                    <h1 class="font-display text-5xl leading-none text-white sm:text-6xl">Sign in and take your seat.</h1>
                    <p class="max-w-2xl text-base leading-7 text-white/70 sm:text-lg">
                        Battle Line now runs on real user accounts. Sign in to create a live duel, join another commander's open battle, and play from your own hidden hand.
                    </p>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="rounded-3xl border border-white/10 bg-black/15 p-4">
                        <p class="text-xs uppercase tracking-[0.24em] text-white/35">Accounts</p>
                        <p class="mt-2 text-sm text-white/70">Session-based login and registration backed by Laravel auth.</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-black/15 p-4">
                        <p class="text-xs uppercase tracking-[0.24em] text-white/35">Lobby</p>
                        <p class="mt-2 text-sm text-white/70">Create a waiting match or join one another user already opened.</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-black/15 p-4">
                        <p class="text-xs uppercase tracking-[0.24em] text-white/35">Fair Play</p>
                        <p class="mt-2 text-sm text-white/70">The server now resolves your seat from your authenticated account.</p>
                    </div>
                </div>
            </section>

            <section class="rounded-[2rem] border border-white/10 bg-black/20 p-6 shadow-2xl shadow-black/20 backdrop-blur-md sm:p-8">
                <div class="mb-6 space-y-2">
                    <p class="text-sm font-semibold uppercase tracking-[0.28em] text-war-gold/75">Sign In</p>
                    <h2 class="font-display text-3xl text-white">Rejoin the command hall</h2>
                </div>

                <form action="{{ route('login') }}" method="post" class="grid gap-5">
                    @csrf

                    <label class="grid gap-2">
                        <span class="text-sm font-medium text-white/75">Email</span>
                        <input
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="rounded-2xl border border-white/10 bg-white/[0.06] px-4 py-3 text-white outline-none transition placeholder:text-white/30 focus:border-war-gold/50 focus:bg-white/[0.09]"
                            required
                            autofocus
                        >
                        @error('email')
                            <span class="text-sm text-red-200">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="grid gap-2">
                        <span class="text-sm font-medium text-white/75">Password</span>
                        <input
                            type="password"
                            name="password"
                            class="rounded-2xl border border-white/10 bg-white/[0.06] px-4 py-3 text-white outline-none transition placeholder:text-white/30 focus:border-war-gold/50 focus:bg-white/[0.09]"
                            required
                        >
                    </label>

                    <label class="flex items-center gap-3 text-sm text-white/70">
                        <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-white/20 bg-white/[0.06] text-war-gold focus:ring-war-gold/40">
                        Keep me signed in on this device
                    </label>

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-2xl border border-war-gold/40 bg-war-gold/15 px-5 py-3 font-semibold text-war-gold transition hover:bg-war-gold/25"
                    >
                        Sign In
                    </button>
                </form>

                <p class="mt-6 text-sm text-white/60">
                    Need an account?
                    <a href="{{ route('register') }}" class="font-semibold text-war-gold transition hover:text-war-ash">Register here</a>
                </p>
            </section>
        </div>
    </main>
</x-layouts.battle-line>
