<div class="card card-primary card-outline">
    <div class="card-header">
        <div class="card-title">Latest Registered Domains</div>
    </div>
    <div class="card-body p-0">
        <table class="table table-striped table-responsive-sm">
            <thead>
            <tr>
                <th style="width: 10px">#</th>
                <th>Domain</th>
                <th>Registered At</th>
                <th>Expires At</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @if($domains->count() > 0)
                @foreach($domains as $domain)
                    <tr>
                        <td>1.</td>
                        <td>{{$domain->name}}</td>
                        <td>
                            {{ $domain->registered_at }}
                        </td>
                        <td>
                            {{ $domain->expires_at }}
                        </td>
                        <td>
                            <a href="{{ route('admin.domains.edit',$domain->uuid) }}" class="btn btn-info">Manage Domain</a>
                        </td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="5" class="bg-info">You No domains registered yet.</td>
                </tr>
            @endif
        </table>
    </div>
</div>
