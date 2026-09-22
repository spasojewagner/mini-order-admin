<?php

namespace App\Filament\Resources\SmsMessages\Pages;

use App\Filament\Resources\SmsMessages\SmsMessageResource;
use Filament\Resources\Pages\ListRecords;

class ListSmsMessages extends ListRecords
{
    protected static string $resource = SmsMessageResource::class;

    // Bez CreateAction: poruke pravi samo agent kroz SmsDraftService.
    protected function getHeaderActions(): array
    {
        return [];
    }
}
