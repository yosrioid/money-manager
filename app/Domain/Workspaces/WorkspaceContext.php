<?php

namespace App\Domain\Workspaces;

use App\Models\Workspace;
use LogicException;

class WorkspaceContext
{
    private ?Workspace $workspace = null;

    public function set(Workspace $workspace): void
    {
        $this->workspace = $workspace;
    }

    public function get(): Workspace
    {
        return $this->workspace
            ?? throw new LogicException('The current workspace has not been resolved.');
    }

    public function has(): bool
    {
        return $this->workspace !== null;
    }
}
