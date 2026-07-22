<?php

namespace Webkul\Marketplace\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Webkul\Marketplace\Http\Requests\AgentLoginRequest;

class AgentSessionController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if (auth()->guard('delivery_agent')->check()) {
            return redirect()->route('marketplace.agent.dashboard.index');
        }

        return view('marketplace::agent.sign-in');
    }

    public function store(AgentLoginRequest $request): RedirectResponse
    {
        $credentials = $request->only(['email', 'password']);

        if (! auth()->guard('delivery_agent')->attempt($credentials)) {
            session()->flash('error', 'These credentials do not match our records.');

            return redirect()->back()->withInput($request->only('email'));
        }

        $agent = auth()->guard('delivery_agent')->user();

        if (! $agent->is_active) {
            auth()->guard('delivery_agent')->logout();

            session()->flash('warning', 'Your delivery agent account is no longer active.');

            return redirect()->back();
        }

        return redirect()->route('marketplace.agent.dashboard.index');
    }

    public function destroy(): RedirectResponse
    {
        auth()->guard('delivery_agent')->logout();

        return redirect()->route('marketplace.agent.session.index');
    }
}
