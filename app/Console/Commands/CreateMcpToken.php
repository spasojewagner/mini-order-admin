<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateMcpToken extends Command
{
    protected $signature = 'app:mcp-token {email : Staff account the token acts as} {--name=MCP client : Label to recognise the token later}';

    protected $description = 'Create an API token for the MCP server (Claude Desktop, Cursor, ...)';

    public function handle(): int
    {
        $user = User::firstWhere('email', $this->argument('email'));

        if (! $user?->isStaff()) {
            $this->error('No staff account with that email. Customers cannot use the MCP server.');

            return self::FAILURE;
        }

        $token = $user->createToken($this->option('name'))->plainTextToken;

        $this->info("Token for {$user->name} ({$user->role}). It is shown only once:");
        $this->line($token);
        $this->newLine();
        $this->line('Endpoint: '.rtrim(config('app.url'), '/').'/mcp/order-admin');
        $this->line('Header:   Authorization: Bearer <token>');

        return self::SUCCESS;
    }
}
