<?php

namespace Database\Seeders;

use App\Domain\Workspaces\ApplyStarterPresets;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class StarterPresetsSeeder extends Seeder
{
    public function __construct(
        private readonly ApplyStarterPresets $applyStarterPresets,
    ) {}

    public function run(Workspace $workspace): void
    {
        $this->applyStarterPresets->apply($workspace);
    }
}
