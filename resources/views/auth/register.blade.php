<x-layouts.battle-line :title="'Register'">
    <main class="mx-auto flex min-h-[calc(100vh-6rem)] w-full max-w-6xl items-center px-5 py-10 sm:px-8 lg:px-10">
        <div class="grid w-full gap-8 lg:grid-cols-[1.05fr_0.95fr]">
            <section class="space-y-6 rounded-[2rem] border border-white/10 bg-white/[0.04] p-7 shadow-2xl shadow-black/20 backdrop-blur-sm sm:p-9">
                <p class="text-sm font-semibold uppercase tracking-[0.3em] text-war-gold/75">New Commander</p>
                <div class="space-y-4">
                    <h1 class="font-display text-5xl leading-none text-white sm:text-6xl">Register and open the line.</h1>
                    <p class="max-w-2xl text-base leading-7 text-white/70 sm:text-lg">
                        Create your account to host battles, join the lobby, and let the server bind each seat to a real commander instead of a simulated name switch.
                    </p>
                </div>
                <div class="rounded-[1.8rem] border border-white/10 bg-black/15 p-5">
                    <p class="text-xs uppercase tracking-[0.24em] text-white/35">What changes now</p>
                    <ul class="mt-4 grid gap-3 text-sm leading-6 text-white/70">
                        <li>One account can create a waiting battle for another user to join.</li>
                        <li>Only participating users can view a live battle state or submit actions.</li>
                        <li>The board automatically shows your own hidden hand after you sign in.</li>
                    </ul>
                </div>
            </section>

            <section class="rounded-[2rem] border border-white/10 bg-black/20 p-6 shadow-2xl shadow-black/20 backdrop-blur-md sm:p-8">
                <div class="mb-6 space-y-2">
                    <p class="text-sm font-semibold uppercase tracking-[0.28em] text-war-gold/75">Register</p>
                    <h2 class="font-display text-3xl text-white">Create your commander profile</h2>
                </div>

                <form action="{{ route('register') }}" method="post" class="grid gap-5">
                    @csrf

                    <label class="grid gap-2">
                        <span class="text-sm font-medium text-white/75">Name</span>
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            class="rounded-2xl border border-white/10 bg-white/[0.06] px-4 py-3 text-white outline-none transition placeholder:text-white/30 focus:border-war-gold/50 focus:bg-white/[0.09]"
                            required
                            autofocus
                        >
                        @error('name')
                            <span class="text-sm text-red-200">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="grid gap-2">
                        <span class="text-sm font-medium text-white/75">Email</span>
                        <input
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="rounded-2xl border border-white/10 bg-white/[0.06] px-4 py-3 text-white outline-none transition placeholder:text-white/30 focus:border-war-gold/50 focus:bg-white/[0.09]"
                            required
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
                        @error('password')
                            <span class="text-sm text-red-200">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="grid gap-2">
                        <span class="text-sm font-medium text-white/75">Confirm password</span>
                        <input
                            type="password"
                            name="password_confirmation"
                            class="rounded-2xl border border-white/10 bg-white/[0.06] px-4 py-3 text-white outline-none transition placeholder:text-white/30 focus:border-war-gold/50 focus:bg-white/[0.09]"
                            required
                        >
                    </label>

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-2xl border border-war-gold/40 bg-war-gold/15 px-5 py-3 font-semibold text-war-gold transition hover:bg-war-gold/25"
                    >
                        Register
                    </button>
                </form>

                <p class="mt-6 text-sm text-white/60">
                    Already enlisted?
                    <a href="{{ route('login') }}" class="font-semibold text-war-gold transition hover:text-war-ash">Sign in here</a>
                </p>
            </section>
        </div>
    </main>
</x-layouts.battle-line>
