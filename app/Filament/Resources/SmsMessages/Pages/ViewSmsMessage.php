<?php

namespace App\Filament\Resources\SmsMessages\Pages;

use App\Filament\Resources\SmsMessages\SmsApprovalActions;
use App\Filament\Resources\SmsMessages\SmsMessageResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSmsMessage extends ViewRecord
{
    protected static string $resource = SmsMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SmsApprovalActions::approve(),
            SmsApprovalActions::reject(),
            EditAction::make()->label('Edit text')->color('gray'),
        ];
    }
}
