<?php

namespace Wemx\PterodactylRegister;

use Illuminate\Support\ServiceProvider;
use Wemx\PterodactylRegister\Commands\InstallCommand;

class CommandsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->commands([
            InstallCommand::class,
        ]);
    }
}