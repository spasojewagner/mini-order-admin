<x-filament-panels::page>
    @if ($newToken)
        <x-filament::section icon="heroicon-o-key" icon-color="success">
            <x-slot name="heading">Copy your token now</x-slot>
            <x-slot name="description">It is shown only this once. Anyone with it can act as you through the MCP server.</x-slot>
            <code style="display:block; padding:.75rem 1rem; border-radius:.5rem; background:rgba(99,102,241,.08); word-break:break-all; font-size:.875rem">{{ $newToken }}</code>
        </x-filament::section>
    @endif

    <x-filament::section>
        <x-slot name="heading">Your tokens</x-slot>
        <x-slot name="description">The MCP server acts with your role ({{ auth()->user()->roleEnum()?->label() }}): it can do what you can do in this panel, nothing more.</x-slot>

        @forelse ($this->getTokens() as $token)
            <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:.75rem 0; border-top:1px solid rgba(148,163,184,.25)">
                <div>
                    <div style="font-weight:600">{{ $token->name }}</div>
                    <div style="font-size:.8rem; opacity:.7">
                        Created {{ $token->created_at->format('d.m.Y H:i') }} ·
                        {{ $token->last_used_at ? 'last used '.$token->last_used_at->diffForHumans() : 'never used' }}
                    </div>
                </div>
                <x-filament::button color="danger" size="sm" outlined wire:click="revoke({{ $token->id }})" wire:confirm="Revoke this token? Clients using it stop working.">
                    Revoke
                </x-filament::button>
            </div>
        @empty
            <p style="opacity:.7">No tokens yet.</p>
        @endforelse
    </x-filament::section>

    <x-filament::section collapsible>
        <x-slot name="heading">Connect Claude Desktop, Cursor or another MCP client</x-slot>
        <p style="margin-bottom:.5rem">Endpoint (Streamable HTTP): <code>{{ $this->endpoint() }}</code>, header <code>Authorization: Bearer &lt;token&gt;</code>.</p>
        <p style="margin-bottom:.5rem">Claude Desktop config, using <code>mcp-remote</code>:</p>
        <pre style="padding:.75rem 1rem; border-radius:.5rem; background:rgba(15,23,42,.06); overflow-x:auto; font-size:.8rem">{
  "mcpServers": {
    "order-admin": {
      "command": "npx",
      "args": ["mcp-remote", "{{ $this->endpoint() }}",
               "--header", "Authorization: Bearer YOUR_TOKEN"]
    }
  }
}</pre>
    </x-filament::section>
</x-filament-panels::page>
