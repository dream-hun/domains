<x-admin-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Contact Details</h3>
                        <div>
                            <a href="{{ route('admin.contacts.edit', $contact) }}" class="btn btn-primary">
                                <i class="bi bi-pencil"></i> Edit Contact
                            </a>
                            <a href="{{ route('admin.contacts.index') }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back to List
                            </a>
                        </div>
                    </div>

                    <div class="card-body">
                        @if($has_differences)
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i>
                                There are differences between local and EPP registry data. Please review and update if necessary.
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="card-title mb-0">Local Database Data</h5>
                                    </div>
                                    <div class="card-body">
                                        <dl class="row">
                                            <dt class="col-sm-4">UUID</dt>
                                            <dd class="col-sm-8">{{ $contact->uuid }}</dd>

                                            <dt class="col-sm-4">Contact ID</dt>
                                            <dd class="col-sm-8">{{ $contact->contact_id ?? 'N/A' }}</dd>

                                            <dt class="col-sm-4">Contact Type</dt>
                                            <dd class="col-sm-8">{{ $contact->contact_type?->value ?? 'N/A' }}</dd>

                                            <dt class="col-sm-4">Name</dt>
                                            <dd class="col-sm-8 {{ isset($differences['name']) ? 'text-danger' : '' }}">
                                                {{ $contact->first_name }} {{ $contact->last_name }}
                                            </dd>

                                            <dt class="col-sm-4">Title</dt>
                                            <dd class="col-sm-8">{{ $contact->title ?? 'N/A' }}</dd>

                                            <dt class="col-sm-4">Organization</dt>
                                            <dd class="col-sm-8 {{ isset($differences['organization']) ? 'text-danger' : '' }}">
                                                {{ $contact->organization ?? 'N/A' }}
                                            </dd>

                                            <dt class="col-sm-4">Email</dt>
                                            <dd class="col-sm-8 {{ isset($differences['email']) ? 'text-danger' : '' }}">
                                                {{ $contact->email }}
                                            </dd>

                                            <dt class="col-sm-4">Phone</dt>
                                            <dd class="col-sm-8 {{ isset($differences['voice']) ? 'text-danger' : '' }}">
                                                {{ $contact->phone }}
                                                @if($contact->phone_extension)
                                                    (Ext: {{ $contact->phone_extension }})
                                                @endif
                                            </dd>

                                            <dt class="col-sm-4">Fax</dt>
                                            <dd class="col-sm-8">
                                                {{ $contact->fax_number ?? 'N/A' }}
                                                @if($contact->fax_ext)
                                                    (Ext: {{ $contact->fax_ext }})
                                                @endif
                                            </dd>

                                            <dt class="col-sm-4">Address</dt>
                                            <dd class="col-sm-8">
                                                <div class="{{ isset($differences['street1']) ? 'text-danger' : '' }}">{{ $contact->address_one }}</div>
                                                @if($contact->address_two)
                                                    <div class="{{ isset($differences['street2']) ? 'text-danger' : '' }}">{{ $contact->address_two }}</div>
                                                @endif
                                                <div class="{{ isset($differences['city']) ? 'text-danger' : '' }}">{{ $contact->city }}</div>
                                                <div class="{{ isset($differences['province']) ? 'text-danger' : '' }}">{{ $contact->state_province }}</div>
                                                <div class="{{ isset($differences['postal_code']) ? 'text-danger' : '' }}">{{ $contact->postal_code }}</div>
                                                <div class="{{ isset($differences['country_code']) ? 'text-danger' : '' }}">{{ $contact->country_code }}</div>
                                            </dd>

                                            <dt class="col-sm-4">Created At</dt>
                                            <dd class="col-sm-8">{{ $contact->created_at->format('Y-m-d H:i:s') }}</dd>

                                            <dt class="col-sm-4">Updated At</dt>
                                            <dd class="col-sm-8">{{ $contact->updated_at->format('Y-m-d H:i:s') }}</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="card-title mb-0">EPP Registry Data</h5>
                                    </div>
                                    <div class="card-body">
                                        @if($epp_contact)
                                            <dl class="row">
                                                <dt class="col-sm-4">Contact ID</dt>
                                                <dd class="col-sm-8">{{ $epp_contact['id'] ?? 'N/A' }}</dd>

                                                <dt class="col-sm-4">Name</dt>
                                                <dd class="col-sm-8 {{ isset($differences['name']) ? 'text-danger' : '' }}">
                                                    {{ $epp_contact['name'] ?? 'N/A' }}
                                                </dd>

                                                <dt class="col-sm-4">Organization</dt>
                                                <dd class="col-sm-8 {{ isset($differences['organization']) ? 'text-danger' : '' }}">
                                                    {{ $epp_contact['organization'] ?? 'N/A' }}
                                                </dd>

                                                <dt class="col-sm-4">Email</dt>
                                                <dd class="col-sm-8 {{ isset($differences['email']) ? 'text-danger' : '' }}">
                                                    {{ $epp_contact['email'] ?? 'N/A' }}
                                                </dd>

                                                <dt class="col-sm-4">Phone</dt>
                                                <dd class="col-sm-8 {{ isset($differences['voice']) ? 'text-danger' : '' }}">
                                                    {{ $epp_contact['voice'] ?? 'N/A' }}
                                                </dd>

                                                <dt class="col-sm-4">Address</dt>
                                                <dd class="col-sm-8">
                                                    @if(isset($epp_contact['streets']) && is_array($epp_contact['streets']))
                                                        @foreach($epp_contact['streets'] as $street)
                                                            <div class="{{ isset($differences['street' . ($loop->index + 1)]) ? 'text-danger' : '' }}">{{ $street }}</div>
                                                        @endforeach
                                                    @endif
                                                    <div class="{{ isset($differences['city']) ? 'text-danger' : '' }}">{{ $epp_contact['city'] ?? 'N/A' }}</div>
                                                    <div class="{{ isset($differences['province']) ? 'text-danger' : '' }}">{{ $epp_contact['province'] ?? 'N/A' }}</div>
                                                    <div class="{{ isset($differences['postal_code']) ? 'text-danger' : '' }}">{{ $epp_contact['postal_code'] ?? 'N/A' }}</div>
                                                    <div class="{{ isset($differences['country_code']) ? 'text-danger' : '' }}">{{ $epp_contact['country_code'] ?? 'N/A' }}</div>
                                                </dd>

                                                <dt class="col-sm-4">Status</dt>
                                                <dd class="col-sm-8">
                                                    @if(isset($epp_contact['status']) && is_array($epp_contact['status']))
                                                        @foreach($epp_contact['status'] as $status)
                                                            <span class="badge bg-info">{{ $status }}</span>
                                                        @endforeach
                                                    @else
                                                        N/A
                                                    @endif
                                                </dd>
                                            </dl>
                                        @else
                                            <div class="text-center text-muted">
                                                <i class="bi bi-exclamation-circle"></i>
                                                <p>No EPP data available</p>
                                                <small>This contact may not exist in the EPP registry or there was an error fetching the data.</small>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($has_differences)
                            <div class="mt-4">
                                <div class="card">
                                    <div class="card-header bg-warning text-dark">
                                        <h5 class="card-title mb-0">
                                            <i class="bi bi-exclamation-triangle"></i>
                                            Differences Found Between Local and EPP Data
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Field</th>
                                                        <th>Local Value</th>
                                                        <th>EPP Value</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($differences as $field => $hasDifference)
                                                        @if($hasDifference)
                                                            <tr>
                                                                <td><strong>{{ ucfirst(str_replace('_', ' ', $field)) }}</strong></td>
                                                                <td>
                                                                    @switch($field)
                                                                        @case('name')
                                                                            {{ $contact->first_name }} {{ $contact->last_name }}
                                                                            @break
                                                                        @case('voice')
                                                                            {{ $contact->phone }}
                                                                            @break
                                                                        @case('street1')
                                                                            {{ $contact->address_one }}
                                                                            @break
                                                                        @case('street2')
                                                                            {{ $contact->address_two ?? 'N/A' }}
                                                                            @break
                                                                        @case('province')
                                                                            {{ $contact->state_province }}
                                                                            @break
                                                                        @default
                                                                            {{ $contact->$field ?? 'N/A' }}
                                                                    @endswitch
                                                                </td>
                                                                <td>
                                                                    @switch($field)
                                                                        @case('voice')
                                                                            {{ $epp_contact['voice'] ?? 'N/A' }}
                                                                            @break
                                                                        @case('street1')
                                                                            {{ $epp_contact['streets'][0] ?? 'N/A' }}
                                                                            @break
                                                                        @case('street2')
                                                                            {{ $epp_contact['streets'][1] ?? 'N/A' }}
                                                                            @break
                                                                        @case('province')
                                                                            {{ $epp_contact['province'] ?? 'N/A' }}
                                                                            @break
                                                                        @default
                                                                            {{ $epp_contact[$field] ?? 'N/A' }}
                                                                    @endswitch
                                                                </td>
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
