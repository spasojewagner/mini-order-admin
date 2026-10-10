<?php

namespace App\Filament\Imports;

use App\Models\Customer;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class CustomerImporter extends Importer
{
    protected static ?string $model = Customer::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('Contact name')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('company_name')
                ->label('Company')
                ->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('tax_id')
                ->label('Tax ID (PIB)')
                ->rules(['nullable', 'digits:9']),
            ImportColumn::make('email')
                ->label('Email')
                ->rules(['nullable', 'email', 'max:255']),
            ImportColumn::make('phone')
                ->label('Phone')
                ->rules(['nullable', 'string', 'max:50']),
            ImportColumn::make('address')
                ->label('Address')
                ->rules(['nullable', 'string', 'max:255']),
        ];
    }

    public function resolveRecord(): ?Customer
    {
        // Same email = same customer: update instead of creating a duplicate.
        if (! empty($this->data['email'])) {
            $existing = Customer::where('email', $this->data['email'])->first();
            if ($existing) {
                return $existing;
            }
        }

        $customer = new Customer;
        $customer->type = ! empty($this->data['company_name']) ? 'company' : 'individual';

        return $customer;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Customer import finished: '.Number::format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failed = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failed).' '.str('row')->plural($failed).' failed.';
        }

        return $body;
    }
}
