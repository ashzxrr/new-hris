@extends('layouts.app')

@section('content')
    <div class="min-h-screen flex items-center justify-center p-6 bg-slate-100">
        <div class="max-w-4xl w-full bg-white shadow-lg rounded-3xl p-8">
            <h1 class="text-4xl font-bold text-slate-900 mb-4">Welcome to Laravel</h1>
            <p class="text-slate-600 mb-6">
                Your application is now configured with MySQL and Tailwind CSS via CDN.
            </p>
            <div class="space-x-3">
                <a href="https://laravel.com/docs" target="_blank" class="inline-block px-5 py-3 rounded-xl bg-slate-900 text-white hover:bg-slate-700 transition">Documentation</a>
                <a href="https://tailwindcss.com" target="_blank" class="inline-block px-5 py-3 rounded-xl border border-slate-300 text-slate-900 hover:bg-slate-50 transition">Tailwind CSS</a>
            </div>
        </div>
    </div>
@endsection
