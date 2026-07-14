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
                ->label('Ime')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),

            ImportColumn::make('email')
                ->label('Email')
                ->rules(['nullable', 'email', 'max:255']),

            ImportColumn::make('phone')
                ->label('Telefon')
                ->rules(['nullable', 'string', 'max:50']),

            ImportColumn::make('company_name')
                ->label('Naziv firme')
                ->rules(['nullable', 'string', 'max:255']),
        ];
    }

    public function resolveRecord(): ?Customer
    {
        // Sprečavanje duplikata po emailu — ako postoji kupac sa istim emailom, ažuriraj ga
        if (! empty($this->data['email'])) {
            $existing = Customer::where('email', $this->data['email'])->first();
            if ($existing) {
                return $existing;
            }
        }

        $customer = new Customer();
        // Tip po tome da li ima naziv firme
        $customer->type = ! empty($this->data['company_name']) ? 'company' : 'individual';

        return $customer;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import kupaca je završen — uspešno uvezeno ' . Number::format($import->successful_rows) . ' ' . str('red')->plural($import->successful_rows, ['red', 'reda', 'redova']) . '.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('red')->plural($failedRowsCount, ['red', 'reda', 'redova']) . ' nije uvezeno.';
        }

        return $body;
    }
}