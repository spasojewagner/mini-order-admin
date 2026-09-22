<?php

namespace App\Filament\Resources\SmsMessages\Pages;

use App\Filament\Resources\SmsMessages\SmsMessageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSmsMessage extends CreateRecord
{
    protected static string $resource = SmsMessageResource::class;
}
