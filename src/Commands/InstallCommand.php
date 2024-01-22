<?php

namespace Wemx\PterodactylRegister\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use function Laravel\Prompts\{progress, text, select, confirm, info, error, warning, spin};

class InstallCommand extends Command
{
    protected $description = 'Install the Register Module';

    protected $signature = 'register:install {--force}';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $client = new Client();
        $file = 'resources/scripts/components/auth/LoginContainer.tsx';

        $res = $client->get("https://raw.githubusercontent.com/VertisanPRO/PterodactylRegister/LoginContainer/{$file}");
        if (sha1($res->getBody()) == sha1(file_get_contents($file))) {
            info('You already have the Register Module installed. If something is wrong, please refer to our Discord Server for help - https://discord.gg/RJfCxC2W3e');
            return;
        }

        $userDetails = posix_getpwuid(fileowner('public'));
        $user = $userDetails['name'] ?? 'www-data';

        $groupDetails = posix_getgrgid(filegroup('public'));
        $group = $groupDetails['name'] ?? 'www-data';

        if (!$this->option('force')) {
            $confirm = confirm(
                label: "Your webserver user has been detected as <fg=green>[{$user}]</>: is this correct?",
                default: true,
            );

            if (!$confirm) {
                $user = select(
                    label: 'Please enter the name of the user running your webserver process. This varies from system to system, but is generally "www-data", "nginx", or "apache".',
                    options: [
                        'www-data' => 'www-data',
                        'nginx' => 'nginx',
                        'apache' => 'apache',
                        'own' => 'Your own user (type after you choose this)'
                    ],
                    default: 'www-data'
                );

                if ($user === 'own')
                    $user = text('Please enter the name of the user running your webserver process');
            }

            $confirm = confirm(
                label: "Your webserver group has been detected as <fg=green>[{$group}]</>: is this correct?",
                default: true,
            );

            if (!$confirm) {
                $group = select(
                    label: 'Please enter the name of the group running your webserver process. Normally this is the same as your user.',
                    options: [
                        'www-data' => 'www-data',
                        'nginx' => 'nginx',
                        'apache' => 'apache',
                        'own' => 'Your own group (type after you choose this)'
                    ],
                    default: 'www-data'
                );

                if ($group === 'own')
                    $group = text('Please enter the name of the group running your webserver process');
            }
        }

        $progress = progress(label: 'Installing the Register Module', steps: 3);
        $progress->start();

        $install = $this->installContainer($file);
        spin(
            fn() => $install,
            'Configuring the login page'
        );

        if (!$install) {
            $progress->finish();

            error('Could not install the Register Module. You are might be using a theme that isn\'t compatible. You must follow the manual installation, if you need help, please refer to our Discord Server for help - https://discord.gg/RJfCxC2W3e');
            return;
        }

        $progress->advance();

        spin(
            fn() => exec('curl -s -L https://github.com/VertisanPRO/PterodactylRegister/releases/latest/download/RegisterModule.tar.gz | tar -xzv'),
            'Downloading the module'
        );

        $progress->advance();

        spin(
            function () {
                exec('chmod -R 755 storage/* bootstrap/cache');
                sleep(1);
            },
            'Setting correct permissions'
        );

        $progress->advance();

        spin(
            fn() => exec('php artisan view:clear && php artisan config:clear'),
            'Clearing cache'
        );

        usleep(800);
        $progress->finish();

        Artisan::call('utils:build');

        $basePath = base_path();
        spin(
            fn() => exec("chown -R {$user}:{$group} {$basePath}/*"),
            'Setting correct permissions'
        );

        info('Successfully installed the Register Module for Pterodactyl. If you have any questions or issues, please reach out to our Discord Server - https://discord.gg/RJfCxC2W3e');
        return;
    }

    private function installContainer(string $file): bool
    {
        $client = new Client();

        $res = $client->get("https://raw.githubusercontent.com/pterodactyl/panel/v1.11.5/{$file}");
        if (sha1($res->getBody()) !== sha1(file_get_contents($file))) {
            $insert = '
                    <div css={tw`mt-6 text-center`}>
                        <Link
                            to={\'/auth/register\'}
                            css={tw`text-xs text-neutral-500 tracking-wide no-underline uppercase`}
                        >
                            Don&apos;t have an account?
                        </Link>
                    </div>
            ';

            $pos = strpos(file_get_contents($file), '                </LoginFormContainer>');

            if (!$pos)
                return false;

            file_put_contents($file, substr_replace(file_get_contents($file), $insert, $pos, 0));
        } else {
            exec('curl -s -L https://github.com/VertisanPRO/PterodactylRegister/releases/latest/download/LoginContainer.tar.gz | tar -xzv');
        }

        return true;
    }
}