<?php

namespace App\Livewire;

use App\Models\Customer;
use Livewire\Component;
use Livewire\WithFileUploads;

class CustomerImport extends Component
{
    use WithFileUploads;

    public $file;              // uploadovani CSV
    public $imported = 0;      // broj uspešnih
    public $failed = 0;        // broj neuspešnih
    public $rowErrors = [];    // greške po redovima
    public $done = false;      // da li je import završen

    protected $rules = [
        'file' => 'required|file|mimes:csv,txt|max:5120', // max 5MB
    ];

    protected $messages = [
        'file.required' => 'Izaberite CSV fajl.',
        'file.mimes' => 'Fajl mora biti CSV.',
        'file.max' => 'Fajl je prevelik (max 5MB).',
    ];

    public function import()
    {
        $this->validate();

        // Resetuj rezultate od prethodnog importa
        $this->imported = 0;
        $this->failed = 0;
        $this->rowErrors = [];
        $this->done = false;

        $path = $this->file->getRealPath();
        $handle = fopen($path, 'r');

        if (!$handle) {
            $this->addError('file', 'Ne mogu da otvorim fajl.');
            return;
        }

        $header = fgetcsv($handle); // prvi red = zaglavlje
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            // Očekivane kolone: name, email, phone, company_name
            $data = [
                'name' => trim($row[0] ?? ''),
                'email' => trim($row[1] ?? ''),
                'phone' => trim($row[2] ?? ''),
                'company_name' => trim($row[3] ?? ''),
            ];

            // Validacija reda
            if ($data['name'] === '') {
                $this->failed++;
                $this->rowErrors[] = "Red {$rowNumber}: ime je obavezno.";
                continue;
            }

            // Provera duplikata po emailu ili telefonu
            $exists = Customer::where(function ($q) use ($data) {
                if ($data['email'] !== '')
                    $q->where('email', $data['email']);
                if ($data['phone'] !== '')
                    $q->orWhere('phone', $data['phone']);
            })->exists();

            if ($exists && ($data['email'] !== '' || $data['phone'] !== '')) {
                $this->failed++;
                $this->rowErrors[] = "Red {$rowNumber}: kupac sa istim emailom ili telefonom već postoji.";
                continue;
            }

            // Kreiraj kupca
            try {
                Customer::create([
                    'type' => $data['company_name'] !== '' ? 'company' : 'individual',
                    'name' => $data['name'],
                    'email' => $data['email'] ?: null,
                    'phone' => $data['phone'] ?: null,
                    'company_name' => $data['company_name'] ?: null,
                ]);
                $this->imported++;
            } catch (\Exception $e) {
                $this->failed++;
                $this->rowErrors[] = "Red {$rowNumber}: greška pri upisu.";
            }
        }

        fclose($handle);
        $this->done = true;
    }

    public function render()
    {
        return view('livewire.customer-import');
    }
}