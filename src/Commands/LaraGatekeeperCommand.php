<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaraGatekeeper\Commands;

use Illuminate\Console\Command;

class LaraGatekeeperCommand extends Command
{
    public $signature = 'lara-gatekeeper';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
