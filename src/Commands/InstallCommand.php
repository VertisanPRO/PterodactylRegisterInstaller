<?php

namespace Wemx\PterodactylRegister\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;

class InstallCommand extends Command
{

    protected $signature = 'register:install';

    protected $description = 'Install the Register Module for Pterodactyl';

    public function handle()
    {
        $client = new Client();

        $res = $client->get('https://raw.githubusercontent.com/VertisanPRO/PterodactylRegister/main/resources/scripts/components/auth/LoginContainer.tsx');
        if (sha1($res->getBody()) == sha1(file_get_contents('resources/scripts/components/auth/LoginContainer.tsx'))) {
            return $this->fail('You already have the Register Module installed, please refer to our Discord Server for help - https://discord.gg/RJfCxC2W3e');
        }

        $this->info('Starting the installation of the Register Module');

        $res = $client->get('https://raw.githubusercontent.com/pterodactyl/panel/v1.11.3/resources/scripts/components/auth/LoginContainer.tsx');
        if (sha1($res->getBody()) !== sha1(file_get_contents('resources/scripts/components/auth/LoginContainer.tsx'))) {
            return $this->fail('Detected changed file, please refer to our Discord Server for help - https://discord.gg/RJfCxC2W3e');
        }

        exec('curl --silent -L https://github.com/VertisanPRO/PterodactylRegister/releases/latest/download/RegisterModule.tar.gz | tar -xzv');

        $this->info('Clearing cache');
        exec('php artisan optimize');

        $this->info('Settings permissions');
        exec('chmod --silent -R 755 storage/* bootstrap/cache');
        exec('chown --silent -R www-data:www-data ' . base_path() . '/*');
        exec('chown --silent -R nginx:nginx ' . base_path() . '/*');
        exec('chown --silent -R apache:apache ' . base_path() . '/*');

        $output = null;
        exec("yarn", $output, $return_var);

        if ($return_var === 1) {
            return $this->fail('Yarn is not installed, please refer to our Discord Server for help - https://discord.gg/RJfCxC2W3e');
        }

        if (!strpos($output[1], 'Validating package.json')) {
            return $this->fail('Detected cmdtest package installed, please refer to our Discord Server for help - https://discord.gg/RJfCxC2W3e');
        }

        $this->info('Building assets (this may take a while)');
        exec('yarn build:production');
        return $this->info('Successfully installed the Register Module for Pterodactyl. If you have any questions or issues, please reach out to our Discord Server - https://discord.gg/RJfCxC2W3e');
    }

    private function fail($value)
    {
        return $this->error('Could not install the Register Module. ' . $value);
    }
}