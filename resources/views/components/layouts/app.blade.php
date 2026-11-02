@props(['title' => null])

@php
    $locale = app()->getLocale();
    $direction = $locale === 'ar' ? 'rtl' : 'ltr';
@endphp

<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $direction }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' — ' : '' }}{{ __('ui.app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    <div class="flex min-h-screen flex-col">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex w-full max-w-5xl flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6">
                <a href="{{ auth()->check() ? route('organizations.select') : route('login') }}" class="flex items-center gap-2 text-start">
                    <span class="inline-flex size-9 items-center justify-center rounded-lg bg-slate-900 text-sm font-bold text-white" aria-hidden="true">FB</span>
                    <span class="text-base font-semibold text-slate-900">{{ __('ui.app.name') }}</span>
                </a>

                @auth
                    <nav class="flex flex-wrap items-center gap-2" aria-label="{{ __('ui.nav.dashboard') }}">
                        <a href="{{ route('organizations.select') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                            {{ __('ui.nav.switch_organization') }}
                        </a>

                        <div class="flex items-center gap-1 rounded-lg border border-slate-200 p-1" role="group" aria-label="{{ __('ui.nav.language') }}">
                            @foreach (\App\Enums\Locale::cases() as $availableLocale)
                                <form method="POST" action="{{ route('locale.update') }}">
                                    @csrf
                                    <input type="hidden" name="locale" value="{{ $availableLocale->value }}">
                                    <button
                                        type="submit"
                                        @class([
                                            'rounded-md px-2.5 py-1.5 text-xs font-semibold transition',
                                            'bg-slate-900 text-white' => app()->getLocale() === $availableLocale->value,
                                            'text-slate-700 hover:bg-slate-100' => app()->getLocale() !== $availableLocale->value,
                                        ])
                                        @if (app()->getLocale() === $availableLocale->value) aria-current="true" @endif
                                    >
                                        {{ $availableLocale->label() }}
                                    </button>
                                </form>
                            @endforeach
                        </div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                                {{ __('ui.nav.logout') }}
                            </button>
                        </form>
                    </nav>
                @endauth
            </div>
        </header>

        <main class="mx-auto w-full max-w-5xl flex-1 px-4 py-8 sm:px-6 sm:py-12">
            @if (session('status'))
                <x-ui.alert type="success" class="mb-6">{{ session('status') }}</x-ui.alert>
            @endif

            @if (session('error'))
                <x-ui.alert type="error" class="mb-6">{{ session('error') }}</x-ui.alert>
            @endif

            {{ $slot }}
        </main>

        <footer class="border-t border-slate-200 bg-white">
            <div class="mx-auto w-full max-w-5xl px-4 py-4 text-xs text-slate-500 sm:px-6">
                {{ __('ui.app.tagline') }}
            </div>
        </footer>
    </div>
</body>
</html>
