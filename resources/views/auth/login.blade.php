<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>HRIS — PT Walet Abdillah Jabji</title>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
            body {
                font-family: 'DM Sans', sans-serif;
            }
        </style>
    </head>
    <body class="min-h-screen bg-slate-50 flex items-center justify-center px-4" style="font-family: 'DM Sans', sans-serif;">
        <div class="w-full max-w-sm bg-white rounded-2xl border border-slate-200 p-10 shadow-sm">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-slate-800">HRIS</h1>
                <p class="mt-2 text-sm text-slate-400">PT Walet Abdillah Jabji</p>
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
                        class="w-full border border-slate-200 rounded-lg px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-300"
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
                        class="w-full border border-slate-200 rounded-lg px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-300"
                        required
                    />
                    @if ($errors->has('password'))
                        <p class="mt-1 text-xs text-red-500">{{ $errors->first('password') }}</p>
                    @endif
                </div>

                <button type="submit" class="w-full bg-slate-800 text-white rounded-lg py-2.5 text-sm font-medium hover:bg-slate-700 transition mt-6">
                    Masuk
                </button>
            </form>
        </div>
    </body>
</html>
