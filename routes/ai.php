<?php

use App\Mcp\Servers\OrderAdminServer;
use Laravel\Mcp\Facades\Mcp;

/*
| MCP server for AI clients (Claude Desktop, Cursor, ...), over Streamable HTTP.
| Every request needs a staff API token (Settings → API tokens in the panel, or
| `php artisan app:mcp-token you@company.com`). Write tools also check the role.
|
| The local stdio transport is not registered: PHP cannot read STDIN without
| blocking on Windows pipes, so it would not work on every machine.
*/

Mcp::web('/mcp/order-admin', OrderAdminServer::class)
    ->middleware(['auth:sanctum', 'staff', 'throttle:120,1']);
