<x-layouts.app :title="__('ui.dashboard.title')">
    <div class="mx-auto w-full max-w-3xl">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
            {{ __('ui.dashboard.welcome', ['name' => auth()->user()->name]) }}
        </h1>

        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.dashboard.organization') }}</p>
                <p class="mt-2 text-base font-semibold text-slate-900">
                    <bdi dir="auto">{{ $organization->name }}</bdi>
                </p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.dashboard.role') }}</p>
                <p class="mt-2 text-base font-semibold text-slate-900">{{ $membership->role_key->label() }}</p>
            </div>
        </div>

        <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">{{ __('ui.dashboard.foundation_title') }}</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('ui.dashboard.foundation_body') }}</p>

            <div class="mt-5">
                <a href="{{ route('organizations.select') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-100">
                    {{ __('ui.nav.switch_organization') }}
                </a>
            </div>
        </section>
    </div>
</x-layouts.app>
