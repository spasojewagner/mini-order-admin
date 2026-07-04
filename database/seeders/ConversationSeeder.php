<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Conversation;
use App\Models\Customer;

class ConversationSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Customer::all();
        if ($customers->isEmpty()) return;

        $channels = ['web', 'viber', 'whatsapp', 'instagram', 'phone'];
        $statuses = ['open', 'waiting', 'closed'];

        foreach (range(1, 8) as $i) {
            $conversation = Conversation::create([
                'customer_id' => $customers->random()->id,
                'channel' => $channels[array_rand($channels)],
                'status' => $statuses[array_rand($statuses)],
                'subject' => 'Upit #' . $i,
            ]);

            // Prva poruka od kupca
            $conversation->messages()->create([
                'sender' => 'customer',
                'body' => 'Zdravo, imam pitanje u vezi porudžbine.',
                'ai_generated' => false,
            ]);

            // Ponekad i admin odgovor
            if (rand(0, 1)) {
                $conversation->messages()->create([
                    'sender' => 'admin',
                    'body' => 'Zdravo, izvolite, kako mogu da pomognem?',
                    'ai_generated' => false,
                ]);
            }
        }
    }
}