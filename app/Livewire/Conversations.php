<?php

namespace App\Livewire;

use App\Models\Conversation;
use Livewire\Component;

class Conversations extends Component
{
    public $selectedId = null;   // izabrana konverzacija
    public $reply = '';          // tekst admin odgovora
    public $statusFilter = '';   // filter po statusu

    // Izaberi konverzaciju
    public function selectConversation($id)
    {
        $this->selectedId = $id;
        $this->reply = '';
    }

    // Pošalji admin odgovor
    public function sendReply()
    {
        $this->validate([
            'reply' => 'required|string|max:2000',
        ], [
            'reply.required' => 'Poruka ne može biti prazna.',
        ]);

        $conversation = Conversation::find($this->selectedId);
        if (! $conversation) return;

        $conversation->messages()->create([
            'sender' => 'admin',
            'body' => $this->reply,
            'ai_generated' => false,
        ]);

        // Kad admin odgovori, status ide na waiting (čeka kupca)
        $conversation->update(['status' => 'waiting']);

        $this->reply = '';
    }

    // Promena statusa konverzacije
    public function setStatus($status)
    {
        $conversation = Conversation::find($this->selectedId);
        if ($conversation) {
            $conversation->update(['status' => $status]);
        }
    }

    public function render()
    {
        $conversations = Conversation::with('customer')
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->get();

        $selected = $this->selectedId
            ? Conversation::with(['customer', 'messages'])->find($this->selectedId)
            : null;

        return view('livewire.conversations', compact('conversations', 'selected'));
    }
}