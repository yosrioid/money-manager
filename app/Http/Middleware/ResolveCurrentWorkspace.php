<?php

namespace App\Http\Middleware;

use App\Domain\Workspaces\WorkspaceContext;
use App\Models\User;
use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveCurrentWorkspace
{
    public function __construct(
        private readonly WorkspaceContext $workspaceContext,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);
        abort_if($user->current_workspace_id === null, 403, 'No active workspace is selected.');

        $workspace = Workspace::query()
            ->whereKey($user->current_workspace_id)
            ->whereHas('memberships', fn ($query) => $query->where('user_id', $user->id))
            ->first();

        abort_if($workspace === null, 403, 'The active workspace is not available.');

        $this->workspaceContext->set($workspace);
        $request->attributes->set('workspace', $workspace);

        return $next($request);
    }
}
