<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\InterfacePreferencesUpdateRequest;
use Illuminate\Http\RedirectResponse;

class InterfacePreferencesController extends Controller
{
    public function update(InterfacePreferencesUpdateRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return back()->with('success', __('settings.interface.updated'));
    }
}
