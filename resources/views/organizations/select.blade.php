<x-layouts.app :title="__('ui.organizations.select_title')">
    <div class="mx-auto w-full max-w-2xl">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">{{ __('ui.organizations.select_title') }}</h1>
        <p class="mt-2 text-sm text-slate-600">{{ __('ui.organizations.select_subtitle') }}</p>

        @if ($memberships->isEmpty())
            <div class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center">
                <h2 class="text-base font-semibold text-slate-900">{{ __('ui.organizations.empty_title') }}</h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-slate-600">{{ __('ui.organizations.empty_body') }}</p>
                <p class="mt-4 text-xs text-slate-500">{{ __('ui.organizations.empty_no_data') }}</p>
            </div>
        @else
            <ul class="mt-8 space-y-3" role="list">
                @foreach ($memberships as $membership)
                    @php($isCurrent = (int) session(\App\Support\OrganizationContext::SESSION_KEY) === (int) $membership->organization_id)
                    <li class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                        <form method="POST" action="{{ route('active-organization.store') }}" class="flex flex-wrap items-center justify-between gap-4">
                            @csrf
                            <input type="hidden" name="organization_id" value="{{ $membership->organization_id }}">

                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="truncate text-base font-semibold text-slate-900">
                                        <bdi dir="auto">{{ $membership->organization->name }}</bdi>
                                    </h2>
                                    @if ($isCurrent)
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">
                                            {{ __('ui.common.active') }}
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-1 text-sm text-slate-600">
                                    {{ __('ui.organizations.role') }}:
                                    <span class="font-medium text-slate-800">{{ $membership->role_key->label() }}</span>
                                </p>
                            </div>

                            <x-ui.button variant="secondary">{{ __('ui.organizations.choose') }}</x-ui.button>
                        </form>
                    </li>
                @endforeach
            </ul>

            @if ($errors->has('organization_id'))
                <div class="mt-4">
                    <x-ui.alert type="error">{{ $errors->first('organization_id') }}</x-ui.alert>
                </div>
            @endif
        @endif
    </div>
</x-layouts.app>
