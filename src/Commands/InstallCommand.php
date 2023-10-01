<?php

namespace Wemx\PterodactylRegister\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{

    protected $signature = 'register:install';

    protected $description = 'Install the Register Module for Pterodactyl';

    public function handle()
    {
        $this->info('Starting the installation of the Register Module');

        if (sha1(file_get_contents(__DIR__ . '/../Files/LoginContainer.tsx')) !== sha1(file_get_contents('resources/scripts/components/auth/LoginContainer.tsx'))) {
            $this->error('Could not install the Register Module. Detected changed file, please refer to our Discord Server for help - https://discord.gg/RJfCxC2W3e');
            return 0;
        }

        exec('curl -L https://github.com/VertisanPRO/PterodactylRegister/releases/latest/download/RegisterModule.tar.gz | tar -xzv 2>/dev/null');

        exec('chmod -R 755 storage/* bootstrap/cache 2>/dev/null');
        exec('chown -R www-data:www-data * 2>/dev/null');
        exec('chown -R nginx:nginx * 2>/dev/null');
        exec('chown -R apache:apache * 2>/dev/null');

        exec('php artisan optimize');

        $output = null;
        exec("yarn", $output, $return_var);

        $this->info(var_dump($output));
        $this->info($return_var);

        if ($return_var === 1) {
            $this->error('Could not install the Register Module. Yarn is not installed, please refer to our Discord Server for help - https://discord.gg/RJfCxC2W3e');
            return 0;
        }

        if (!strpos($output[1], 'Validating package.json')) {
            $this->error('Could not install the Register Module. Detected cmdtest package installed, please refer to our Discord Server for help - https://discord.gg/RJfCxC2W3e');
            return 0;
        }

        exec('yarn build:production');
        return $this->info('Successfully installed the Register Module for Pterodactyl. If you have any questions or issues, please reach out to our Discord Server - https://discord.gg/RJfCxC2W3e');
    }
}