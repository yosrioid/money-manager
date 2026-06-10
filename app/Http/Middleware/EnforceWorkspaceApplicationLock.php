<?php

namespace App\Http\Middleware;

use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Symfony\Component\HttpFoundation\Response;

class EnforceWorkspaceApplicationLock
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $workspace = $request->attributes->get('workspace');

        abort_unless($workspace instanceof Workspace, 403);

        if ($workspace->application_lock_minutes === 0) {
            return $next($request);
        }

        $sessionKey = "workspace_lock_activity.{$workspace->id}";
        $lastActivityAt = (int) $request->session()->get($sessionKey, 0);
        $now = Date::now()->unix();
        $expiresAt = $lastActivityAt + ($workspace->application_lock_minutes * 60);
        $passwordConfirmedAt = (int) $request->session()->get('auth.password_confirmed_at', 0);

        if ($lastActivityAt > 0 && $now >= $expiresAt && $passwordConfirmedAt < $expiresAt) {
            return redirect()->guest(route('password.confirm'));
        }

        $response = $next($request);

        $request->session()->put($sessionKey, $now);

        return $response;
    }
}
