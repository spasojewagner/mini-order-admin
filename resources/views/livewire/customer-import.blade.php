<div>
    <div class="card" style="margin-bottom:16px;">
        <label>CSV fajl sa kupcima</label>
        <input type="file" wire:model="file" accept=".csv,.txt">
        @error('file') <div style="color:#dc2626; font-size:13px; margin-top:4px;">{{ $message }}</div> @enderror

        <div wire:loading wire:target="file" style="color:#6b7280; font-size:13px; margin-top:8px;">Učitavanje fajla...
        </div>

        <div style="margin-top:12px; font-size:13px; color:#6b7280;">
            Očekivane kolone (sa zaglavljem u prvom redu): name, email, phone, company_name
        </div>

        <div style="margin-top:16px;">
            <button class="btn" wire:click="import" wire:loading.attr="disabled" wire:target="import">
                <span wire:loading.remove wire:target="import">Uvezi</span>
                <span wire:loading wire:target="import">Uvozim...</span>
            </button>
        </div>
    </div>

    @if($done)
        <div class="card">
            <h3 style="margin-top:0;">Rezultat importa</h3>
            <p style="color:#166534;">Uspešno uvezeno: <strong>{{ $imported }}</strong></p>
            <p style="color:#dc2626;">Neuspešno: <strong>{{ $failed }}</strong></p>

            @if(count($rowErrors))
                <div style="margin-top:12px;">
                    <strong>Greške po redovima:</strong>
                    <ul style="margin:8px 0 0; padding-left:20px; max-height:240px; overflow:auto;">
                        @foreach($rowErrors as $rowError)
                            <li style="color:#dc2626; font-size:13px;">{{ $rowError }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif
</div>