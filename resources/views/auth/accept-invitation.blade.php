<x-layouts.app :title="__('ui.auth.invitation_title')">
    <div class="mx-auto w-full max-w-md">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">{{ __('ui.auth.invitation_title') }}</h1>
            <p class="mt-2 text-sm text-slate-600">
                {{ __('ui.auth.invitation_subtitle', ['organization' => $invitation->organization->name]) }}
            </p>

            <dl class="mt-5 grid gap-2 rounded-lg bg-slate-50 p-4 text-sm">
                <div class="flex flex-wrap justify-between gap-2">
                    <dt class="font-medium text-slate-600">{{ __('ui.organizations.role') }}</dt>
                    <dd class="font-semibold text-slate-900">{{ $invitation->role_key->label() }}</dd>
                </div>
                <div class="flex flex-wrap justify-between gap-2">
                    <dt class="font-medium text-slate-600">{{ __('ui.auth.invited_email') }}</dt>
                    <dd class="font-semibold text-slate-900"><bdi dir="ltr">{{ $invitation->email }}</bdi></dd>
                </div>
            </dl>

            @if ($errors->has('invitation'))
                <div class="mt-4">
                    <x-ui.alert type="error">{{ $errors->first('invitation') }}</x-ui.alert>
                </div>
            @endif

            <form method="POST" action="{{ $submitUrl }}" class="mt-6 space-y-5">
                @csrf

                <x-ui.field
                    name="name"
                    :label="__('ui.auth.name')"
                    autocomplete="name"
                    :hint="__('ui.auth.name_hint')"
                    dir="auto"
                />

                <x-ui.field
                    name="email"
                    :label="__('ui.auth.invited_email')"
                    type="email"
                    :value="$invitation->email"
                    autocomplete="username"
                    dir="ltr"
                    required
                    readonly
                />

                <x-ui.field
                    name="password"
                    :label="__('ui.auth.password')"
                    type="password"
                    autocomplete="new-password"
                    :hint="__('ui.auth.password_hint')"
                    required
                />

                <x-ui.field
                    name="password_confirmation"
                    :label="__('ui.auth.password_confirmation')"
                    type="password"
                    autocomplete="new-password"
                    required
                />

                <x-ui.button class="w-full">{{ __('ui.auth.accept_invitation') }}</x-ui.button>
            </form>
        </div>
    </div>
</x-layouts.app>
