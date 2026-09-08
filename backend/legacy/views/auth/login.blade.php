@extends('layouts.app')

@section('title', 'Sign In')

@section('content')
    <div class="mx-auto max-w-md">
        <div class="rounded-2xl bg-white p-8 shadow-sm">
            <h1 class="text-2xl font-bold text-slate-900">Sign In</h1>
            <p class="mt-1 text-sm text-slate-600">Access the management portal or your officer dashboard.</p>

            <form method="POST" action="{{ route('login.attempt') }}" class="mt-6 space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">Email address</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                           class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                    <input type="password" name="password" id="password" required
                           class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    Remember me
                </label>

                <button type="submit" class="btn-primary w-full justify-center">Sign In</button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-600">
                Not registered yet?
                <a href="{{ route('register.create') }}" class="font-semibold text-brand-700 hover:underline">Create an account</a>
            </p>
        </div>
    </div>
@endsection
