<?php

use App\Mcp\Servers\OrderAdminServer;
use Laravel\Mcp\Facades\Mcp;

// Lokalni (stdio) transport - za Claude Desktop.
// Ne radi na Windowsu: StdioTransport koristi non-blocking STDIN,
// sto PHP na Windows pipe-ovima ne podrzava.
// Mcp::local('order-admin', OrderAdminServer::class);

Mcp::web('/mcp/order-admin', OrderAdminServer::class);