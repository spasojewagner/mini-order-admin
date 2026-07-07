<div>
    <div style="margin-bottom:16px;">
        <select wire:model.live="statusFilter" style="width:auto;">
            <option value="">Svi statusi</option>
            <option value="open">Open</option>
            <option value="waiting">Waiting</option>
            <option value="closed">Closed</option>
        </select>
    </div>

    <div style="display:flex; gap:16px; align-items:flex-start;">
        <div style="flex:0 0 300px;">
            @foreach($conversations as $conversation)
                <div wire:click="selectConversation({{ $conversation->id }})"
                     style="background:{{ $selectedId === $conversation->id ? '#eff6ff' : '#fff' }}; border:1px solid #e5e7eb; border-radius:8px; padding:12px; margin-bottom:8px; cursor:pointer;">
                    <div style="font-weight:600;">{{ $conversation->customer->name ?? 'Nepoznat' }}</div>
                    <div style="font-size:13px; color:#6b7280;">{{ $conversation->subject }}</div>
                    <div style="font-size:12px; margin-top:4px;">
                        <span style="text-transform:uppercase;">{{ $conversation->channel }}</span> ·
                        <span>{{ $conversation->status }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <div style="flex:1;">
            @if($selected)
                <div class="card">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                        <div>
                            <strong>{{ $selected->customer->name ?? 'Nepoznat' }}</strong>
                            <span style="font-size:13px; color:#6b7280;"> · {{ $selected->channel }} · {{ $selected->status }}</span>
                        </div>
                        <div style="display:flex; gap:4px;">
                            <button class="btn btn-secondary" wire:click="setStatus('open')" style="font-size:12px;">Open</button>
                            <button class="btn btn-secondary" wire:click="setStatus('waiting')" style="font-size:12px;">Waiting</button>
                            <button class="btn btn-secondary" wire:click="setStatus('closed')" style="font-size:12px;">Closed</button>
                        </div>
                    </div>

                    <div style="max-height:360px; overflow:auto; margin-bottom:16px;">
                        @foreach($selected->messages as $message)
                            <div style="margin-bottom:10px; display:flex; justify-content:{{ $message->sender === 'admin' ? 'flex-end' : 'flex-start' }};">
                                <div style="max-width:70%; padding:8px 12px; border-radius:8px; background:{{ $message->sender === 'admin' ? '#2563eb' : '#f3f4f6' }}; color:{{ $message->sender === 'admin' ? '#fff' : '#111' }};">
                                    <div style="font-size:11px; opacity:0.7; margin-bottom:2px;">{{ $message->sender }}</div>
                                    {{ $message->body }}
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($selected->status !== 'closed')
                        <div>
                            <textarea wire:model="reply" rows="2" placeholder="Napiši odgovor..."></textarea>
                            @error('reply') <div style="color:#dc2626; font-size:13px; margin-top:4px;">{{ $message }}</div> @enderror
                            <div style="margin-top:8px;">
                                <button class="btn" wire:click="sendReply">Pošalji</button>
                            </div>
                        </div>
                    @else
                        <div style="color:#6b7280; font-size:14px;">Konverzacija je zatvorena.</div>
                    @endif
                </div>
            @else
                <div class="card">Izaberi konverzaciju sa leve strane.</div>
            @endif
        </div>
    </div>
</div>