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

            <form action="{{ route('login') }}" method="POST" class="space-y-6">
                @csrf

                <div>
                    <label for="username" class="block text-sm font-medium text-slate-600 mb-1">Username</label>
                    <input
                        id="username"
                        name="username"
                        type="text"
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
                    <input
                        id="password"
                        name="password"
                        type="password"
                        class="w-full border border-[#E5E7EB] rounded-lg px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-300"
                        required
                    />
                    @if ($errors->has('password'))
                        <p class="mt-1 text-xs text-red-500">{{ $errors->first('password') }}</p>
                    @endif
                </div>

               <button type="submit" class="w-full pbtn pbtn-primary">
    <span class="pbtn-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
            <path d="M10 17l5-5-5-5"/>
            <path d="M15 12H3"/>
        </svg>
    </span>
    <span>Masuk</span>
</button>
            </form>
        </div>
    </body>
</html>
