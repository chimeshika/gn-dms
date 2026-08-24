@extends('layouts.app')

@section('title', 'Welcome - '.config('app.name'))

@section('content')
    <section class="rounded-2xl bg-white p-8 shadow-sm sm:p-12">
        <div class="max-w-3xl">
            <h1 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                Grama Niladhari & Public Officers Management System
            </h1>
            <p class="mt-4 text-lg leading-relaxed text-slate-600">
                A centralized platform for the registration, verification, transfer and official
                correspondence of Grama Niladhari officers across all 25 districts. Generate
                appointment, confirmation, promotion, transfer and retirement letters with
                dynamic signatories and automatic copy routing — in Sinhala, Tamil and English.
            </p>

            <div class="mt-8 grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <div class="text-sm font-semibold text-slate-900">Officer Self-Registration</div>
                    <p class="mt-1 text-sm text-slate-600">Register once, get verified by your Divisional Secretariat, then track your full service timeline.</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <div class="text-sm font-semibold text-slate-900">Automated Letters & PDF</div>
                    <p class="mt-1 text-sm text-slate-600">Batch letters with live WYSIWYG editing, Unicode Sinhala/Tamil PDF rendering and bulk export.</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <div class="text-sm font-semibold text-slate-900">4-Level Access Control</div>
                    <p class="mt-1 text-sm text-slate-600">Officer, Divisional Admin, District Admin and Home Affairs Main Admin — each with scoped powers.</p>
                </div>
            </div>

            <div class="mt-10 flex flex-wrap gap-4">
                <a href="{{ route('register.create') }}" class="btn-primary">Officer Registration</a>
                <a href="{{ route('login') }}" class="btn-ghost">Sign In</a>
            </div>
        </div>
    </section>
@endsection
