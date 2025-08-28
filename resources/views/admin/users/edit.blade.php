<x-admin-layout>
    @section('page-title')
        Update {{$user->first_name}} {{$user->last_name}}
    @endsection
    <div class="card">
        <div class="card-header">
            {{ trans('global.edit') }} {{ trans('cruds.user.title_singular') }}
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.users.update', [$user->id]) }}" enctype="multipart/form-data">
                @method('PUT')
                @csrf
                <div class="row">
                    <div class="form-group col-md-6">
                        <label class="required" for="name">First Name</label>
                        <input class="form-control {{ $errors->has('first_name') ? 'is-invalid' : '' }}" type="text" name="first_name"
                               id="first_name" value="{{ old('first_name', $user->first_name) }}" required>
                        @if ($errors->has('first_name'))
                            <span class="text-danger">{{ $errors->first('first_name') }}</span>
                        @endif
                    </div>
                    <div class="form-group col-md-6">
                        <label class="required" for="last_name">Last Name</label>
                        <input class="form-control {{ $errors->has('last_name') ? 'is-invalid' : '' }}" type="text" name="last_name"
                               id="last_name" value="{{ old('last_name', $user->last_name) }}" required>
                        @if ($errors->has('last_name'))
                            <span class="text-danger">{{ $errors->first('last_name') }}</span>
                        @endif
                    </div>
                </div>

                <div class="form-group">
                    <label class="required" for="email">{{ trans('cruds.user.fields.email') }}</label>
                    <input class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" type="email"
                           name="email" id="email" value="{{ old('email', $user->email) }}" required>
                    @if ($errors->has('email'))
                        <span class="text-danger">{{ $errors->first('email') }}</span>
                    @endif
                    <span class="help-block">{{ trans('cruds.user.fields.email_helper') }}</span>
                </div>

                <div class="form-group">
                    <label class="required" for="roles">{{ trans('cruds.user.fields.roles') }}</label>
                    <div style="padding-bottom: 4px">
                        <span class="btn btn-info btn-xs select-all"
                              style="border-radius: 0">{{ trans('global.select_all') }}</span>
                        <span class="btn btn-info btn-xs deselect-all"
                              style="border-radius: 0">{{ trans('global.deselect_all') }}</span>
                    </div>
                    <select class="form-control select2 {{ $errors->has('roles') ? 'is-invalid' : '' }}" name="roles[]"
                            id="roles" multiple required>
                        @foreach ($roles as $id => $role)
                            <option value="{{ $id }}"
                                {{ in_array($id, old('roles', [])) || $user->roles->contains($id) ? 'selected' : '' }}>
                                {{ $role }}</option>
                        @endforeach
                    </select>
                    @if ($errors->has('roles'))
                        <span class="text-danger">{{ $errors->first('roles') }}</span>
                    @endif
                    <span class="help-block">{{ trans('cruds.user.fields.roles_helper') }}</span>
                </div>

                <div class="form-group">
                    <button class="btn btn-danger" type="submit">
                        {{ trans('global.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>



