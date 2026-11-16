<?php

namespace Saucebase\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use InterNACHI\Modular\Support\ModuleRegistry;
use Saucebase\Core\Console\Traits\FindsModuleSeeders;

class DemoAccountsCommand extends Command
{
    use FindsModuleSeeders;

    protected $signature = 'modules:demo-accounts
        {--state=showcase : The state whose accounts to list (a template\'s is default)}
        {--json : As JSON, for the installer}';

    protected $description = 'List the demo accounts a state\'s seeders declare (#[DemoAccount])';

    public function handle(ModuleRegistry $registry): int
    {
        $state = Str::studly((string) $this->option('state'));
        $accounts = $this->demoAccounts($this->seeders($registry->modules(), fn (string $studly) => "{$state}{$studly}DatabaseSeeder"));

        if ($this->option('json')) {
            $this->line((string) json_encode($accounts));

            return self::SUCCESS;
        }

        if ($accounts === []) {
            $this->components->info("No module declares demo accounts for the \"{$this->option('state')}\" state.");

            return self::SUCCESS;
        }

        $this->table(['Module', 'Account', 'What it sees', 'Start here'], array_map(
            fn (array $account): array => [$account['module'], $account['email'], $account['description'], $account['featured'] ? 'yes' : ''],
            $accounts,
        ));
        $this->line('  They sign in with DEMO_PASSWORD from .env.');

        return self::SUCCESS;
    }
}
