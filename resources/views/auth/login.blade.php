<x-layouts.app :title="__('ui.auth.login_title')">
    <div class="mx-auto w-full max-w-md">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">{{ __('ui.auth.login_title') }}</h1>
            <p class="mt-2 text-sm text-slate-600">{{ __('ui.auth.login_subtitle') }}</p>

            <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-5">
                @csrf

                <x-ui.field
                    name="email"
                    :label="__('ui.auth.email')"
                    type="email"
                    autocomplete="username"
                    dir="ltr"
                    required
                    autofocus
                />

                <x-ui.field
                    name="password"
                    :label="__('ui.auth.password')"
                    type="password"
                    autocomplete="current-password"
                    required
                />

                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input
                        type="checkbox"
                        name="remember"
                        value="1"
                        @checked(old('remember'))
                        class="size-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                    >
                    <span>{{ __('ui.auth.remember') }}</span>
                </label>

                <x-ui.button class="w-full">{{ __('ui.auth.sign_in') }}</x-ui.button>
            </form>

            <p class="mt-6 border-t border-slate-200 pt-4 text-xs text-slate-500">
                {{ __('ui.auth.invitation_only') }}
            </p>
        </div>
    </div>
</x-layouts.app>
