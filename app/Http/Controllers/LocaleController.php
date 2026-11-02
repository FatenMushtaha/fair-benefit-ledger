<?php

namespace App\Http\Controllers;

use App\Actions\UpdateLocale;
use App\Http\Requests\UpdateLocaleRequest;
use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    public function update(UpdateLocaleRequest $request, UpdateLocale $updateLocale): RedirectResponse
    {
        $updateLocale->handle($request->user(), $request->validated('locale'));

        return back()->with('status', __('ui.status.locale_updated'));
    }
}
