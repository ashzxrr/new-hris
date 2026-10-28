<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>HRIS — PT Walet Abdillah Jabji</title>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            body {
                font-family: 'DM Sans', sans-serif;
            }
        </style>
    </head>
    <body class="min-h-screen bg-[#F8FAFC] flex items-center justify-center px-4" style="font-family: 'DM Sans', sans-serif;">
        <div class="w-full max-w-sm bg-white rounded-2xl border border-[#E5E7EB] p-10 shadow-sm">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-slate-800">HRIS</h1>
                <p class="mt-2 text-sm text-slate-400">PT Walet Abdillah Jabli</p>
            </div>

            <form action="{{ route('login') }}" method="POST" class="space-y-6" autocomplete="on">
                @csrf

                <div>
                    <label for="username" class="block text-sm font-medium text-slate-600 mb-1">Username</label>
                    <input
                        id="username"
                        name="username"
                        type="text"
                        autocomplete="username"
                        value="{{ old('username') }}"
                        class="w-full border border-[#E5E7EB] rounded-lg px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-300"
                        required
                    />
                    @if ($errors->has('username'))
                        <p class="mt-1 text-xs text-red-500">{{ $errors->first('username') }}</p>
                    @endif
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-600 mb-1">Password</label>
                    <div class="relative flex items-center border border-[#E5E7EB] rounded-lg focus-within:ring-2 focus-within:ring-slate-300">
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            class="w-full border-0 bg-transparent rounded-lg px-4 py-2.5 pr-12 text-sm text-slate-900 focus:outline-none focus:ring-0"
                            required
                        />
                        <button
                            id="toggle-password"
                            type="button"
                            class="absolute inset-y-0 right-0 flex items-center px-4 text-slate-400 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-slate-300 rounded-r-lg"
                            aria-label="Tampilkan password"
                            aria-pressed="false"
                            title="Tampilkan password"
                        >
                            <svg data-eye-icon viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                            <svg data-eye-off-icon viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="hidden w-5 h-5">
                                <path d="m3 3 18 18" />
                                <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8" />
                                <path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.5 0 10 7 10 7a15.7 15.7 0 0 1-3.1 3.9" />
                                <path d="M6.6 6.6C3.6 8.5 2 12 2 12s3.5 7 10 7c1.4 0 2.7-.3 3.8-.8" />
                            </svg>
                        </button>
                    </div>
                    @if ($errors->has('password'))
                        <p class="mt-1 text-xs text-red-500">{{ $errors->first('password') }}</p>
                    @endif
                </div>

                <label for="remember" class="flex items-center gap-2 text-sm text-slate-600">
                    <input
                        id="remember"
                        name="remember"
                        type="checkbox"
                        value="1"
                        @checked(old('remember'))
                        class="h-4 w-4 rounded border-slate-300 text-slate-700 focus:ring-slate-400"
                    />
                    <span>Simpan login di perangkat ini</span>
                </label>

                <button type="submit" class="w-full pbtn pbtn-primary">
                    <span class="pbtn-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                            <path d="M10 17l5-5-5-5" />
                            <path d="M15 12H3" />
                        </svg>
                    </span>
                    <span>Masuk</span>
                </button>
            </form>
        </div>

        <script>
            const loginForm = document.querySelector('form[action="{{ route('login') }}"]');
            const usernameInput = document.getElementById('username');
            const rememberCheckbox = document.getElementById('remember');
            const rememberedUsernameKey = 'hris.remembered-username';
            const passwordInput = document.getElementById('password');
            const passwordToggle = document.getElementById('toggle-password');
            const eyeIcon = passwordToggle.querySelector('[data-eye-icon]');
            const eyeOffIcon = passwordToggle.querySelector('[data-eye-off-icon]');

            try {
                const rememberedUsername = localStorage.getItem(rememberedUsernameKey);

                if (rememberedUsername) {
                    if (!usernameInput.value) {
                        usernameInput.value = rememberedUsername;
                    }

                    rememberCheckbox.checked = true;
                }

                loginForm.addEventListener('submit', () => {
                    if (rememberCheckbox.checked) {
                        localStorage.setItem(rememberedUsernameKey, usernameInput.value.trim());
                    } else {
                        localStorage.removeItem(rememberedUsernameKey);
                    }
                });
            } catch (error) {
                // Browser storage may be unavailable; native password-manager autofill still works.
            }

            passwordToggle.addEventListener('click', () => {
                const isVisible = passwordInput.type === 'password';
                const label = isVisible ? 'Sembunyikan password' : 'Tampilkan password';

                passwordInput.type = isVisible ? 'text' : 'password';
                passwordToggle.setAttribute('aria-label', label);
                passwordToggle.setAttribute('title', label);
                passwordToggle.setAttribute('aria-pressed', String(isVisible));
                eyeIcon.classList.toggle('hidden', isVisible);
                eyeOffIcon.classList.toggle('hidden', !isVisible);
            });
        </script>
    </body>
</html>
