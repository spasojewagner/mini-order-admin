<?php

namespace App\Filament\Resources\SmsMessages\Pages;

use App\Filament\Resources\SmsMessages\SmsMessageResource;
use Filament\Resources\Pages\ListRecords;

class ListSmsMessages extends ListRecords
{
    protected static string $resource = SmsMessageResource::class;

    // No create button: drafts come only from the AI agent through SmsDraftService.
    protected function getHeaderActions(): array
    {
        return [];
    }
}
