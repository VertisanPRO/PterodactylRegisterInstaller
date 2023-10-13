<?php

namespace Wemx\PterodactylRegister\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use Laravel\Prompts;

class InstallCommand extends Command
{

    protected $signature = 'register:install';

    protected $description = 'Install the Register Module for Pterodactyl';

    public function handle()
    {
        $progress = Prompts::progress(label: 'Updating users', steps: 5, hint: 'This may take a while');
        $progress->start();

        $client = new Client();

        $res = $client->get('https://raw.githubusercontent.com/VertisanPRO/PterodactylRegister/LoginContainer/resources/scripts/components/auth/LoginContainer.tsx');
        if (sha1($res->getBody()) == sha1(file_get_contents('resources/scripts/components/auth/LoginContainer.tsx'))) {
            $progress->finish();
            return $this->fail('You already have the Register Module installed');
        }

        $this->info('Starting the installation of the Register Module');
        $progress->advance();

        $res = $client->get('https://raw.githubusercontent.com/pterodactyl/panel/v1.11.3/resources/scripts/components/auth/LoginContainer.tsx');
        if (sha1($res->getBody()) !== sha1(file_get_contents('resources/scripts/components/auth/LoginContainer.tsx'))) {
            $progress->finish();
            return $this->fail('Detected changed file');
        }

        exec('curl --silent -L https://github.com/VertisanPRO/PterodactylRegister/releases/latest/download/RegisterModule.tar.gz | tar -xzv');
        exec('curl --silent -L https://github.com/VertisanPRO/PterodactylRegister/releases/latest/download/LoginContainer.tar.gz | tar -xzv');
        $progress->advance();

        $this->info('Clearing cache');
        exec('php artisan optimize');
        $progress->advance();

        $output = null;
        exec("yarn", $output, $return_var);
        $progress->advance();

        if ($return_var === 1) {
            $progress->finish();
            return $this->fail('Yarn is not installed');
        }

        if (!strpos($output[1], 'Validating package.json')) {
            $progress->finish();
            return $this->fail('Detected cmdtest package installed');
        }

        $this->info('Building assets (this may take a while)');
        $progress->advance();
        exec('yarn build:production');
        $progress->finish();
        return $this->info('Successfully installed the Register Module for Pterodactyl. If you have any questions or issues, please reach out to our Discord Server - https://discord.gg/RJfCxC2W3e');
    }

    private function fail($value)
    {
        return $this->error('Could not install the Register Module. ' . $value . ', please refer to our Discord Server for help - https://discord.gg/RJfCxC2W3e');
    }
}