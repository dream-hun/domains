<?php

declare(strict_types=1);

namespace App\Livewire\Checkout;

use App\Models\Contact;
use App\Models\Country;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

final class ContactCreateModal extends Component
{
    public bool $showModal = false;

    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $phone = '';

    public string $organization = '';

    public string $address_one = '';

    public string $address_two = '';

    public string $city = '';

    public string $state_province = '';

    public string $postal_code = '';

    public string $country_code = '';

    public bool $is_primary = false;

    #[On('open-contact-modal')]
    public function openModal(): void
    {
        $this->showModal = true;
        $this->resetForm();
    }

    #[On('close-contact-modal')]
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'organization' => 'nullable|string|max:255',
            'address_one' => 'required|string|max:255',
            'address_two' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'state_province' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
            'country_code' => 'required|string|size:2|exists:countries,iso_alpha2',
            'is_primary' => 'boolean',
        ]);

        /** @var Contact $contact */
        $contact = auth()->user()->contacts()->create($validated);

        $this->dispatch('contact-created', contactId: $contact->id);
        $this->closeModal();

        session()->flash('success', 'Contact created successfully!');
    }

    /**
     * @return Collection<int, Country>
     */
    #[Computed]
    public function countries(): Collection
    {
        return Country::query()
            ->whereNotNull('iso_alpha2')
            ->orderBy('name')
            ->get(['iso_alpha2', 'name']);
    }

    public function render(): Factory|View
    {
        return view('livewire.checkout.contact-create-modal');
    }

    private function resetForm(): void
    {
        $this->first_name = '';
        $this->last_name = '';
        $this->email = '';
        $this->phone = '';
        $this->organization = '';
        $this->address_one = '';
        $this->address_two = '';
        $this->city = '';
        $this->state_province = '';
        $this->postal_code = '';
        $this->country_code = '';
        $this->is_primary = false;
        $this->resetErrorBag();
    }
}
