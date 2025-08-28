<x-admin-layout>
    <div class="card">
        <div class="card-header">
            {{ trans('global.create') }} {{ trans('cruds.setting.title_singular') }}
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.settings.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="email">{{ trans('cruds.setting.fields.email') }}</label>
                    <input class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" type="email" name="email"
                        id="email" value="{{ old('email') }}">
                    @if ($errors->has('email'))
                        <div class="invalid-feedback">
                            {{ $errors->first('email') }}
                        </div>
                    @endif
                    <span class="help-block">{{ trans('cruds.setting.fields.email_helper') }}</span>
                </div>
                <div class="form-group">
                    <label class="required" for="phone">{{ trans('cruds.setting.fields.phone') }}</label>
                    <input class="form-control {{ $errors->has('phone') ? 'is-invalid' : '' }}" type="text"
                        name="phone" id="phone" value="{{ old('phone', '') }}" required>
                    @if ($errors->has('phone'))
                        <div class="invalid-feedback">
                            {{ $errors->first('phone') }}
                        </div>
                    @endif
                    <span class="help-block">{{ trans('cruds.setting.fields.phone_helper') }}</span>
                </div>
                <div class="form-group">
                    <label for="address">{{ trans('cruds.setting.fields.address') }}</label>
                    <input class="form-control {{ $errors->has('address') ? 'is-invalid' : '' }}" type="text"
                        name="address" id="address" value="{{ old('address', '') }}">
                    @if ($errors->has('address'))
                        <div class="invalid-feedback">
                            {{ $errors->first('address') }}
                        </div>
                    @endif
                    <span class="help-block">{{ trans('cruds.setting.fields.address_helper') }}</span>
                </div>
                <div class="form-group">
                    <label for="twitter">{{ trans('cruds.setting.fields.twitter') }}</label>
                    <input class="form-control {{ $errors->has('twitter') ? 'is-invalid' : '' }}" type="text"
                        name="twitter" id="twitter" value="{{ old('twitter', '') }}">
                    @if ($errors->has('twitter'))
                        <div class="invalid-feedback">
                            {{ $errors->first('twitter') }}
                        </div>
                    @endif
                    <span class="help-block">{{ trans('cruds.setting.fields.twitter_helper') }}</span>
                </div>
                <div class="form-group">
                    <label for="instagram">{{ trans('cruds.setting.fields.instagram') }}</label>
                    <input class="form-control {{ $errors->has('instagram') ? 'is-invalid' : '' }}" type="text"
                        name="instagram" id="instagram" value="{{ old('instagram', '') }}">
                    @if ($errors->has('instagram'))
                        <div class="invalid-feedback">
                            {{ $errors->first('instagram') }}
                        </div>
                    @endif
                    <span class="help-block">{{ trans('cruds.setting.fields.instagram_helper') }}</span>
                </div>
                <div class="form-group">
                    <label for="youtube">{{ trans('cruds.setting.fields.youtube') }}</label>
                    <input class="form-control {{ $errors->has('youtube') ? 'is-invalid' : '' }}" type="text"
                        name="youtube" id="youtube" value="{{ old('youtube', '') }}">
                    @if ($errors->has('youtube'))
                        <div class="invalid-feedback">
                            {{ $errors->first('youtube') }}
                        </div>
                    @endif
                    <span class="help-block">{{ trans('cruds.setting.fields.youtube_helper') }}</span>
                </div>
                <div class="form-group">
                    <label for="linkedin">{{ trans('cruds.setting.fields.linkedin') }}</label>
                    <input class="form-control {{ $errors->has('linkedin') ? 'is-invalid' : '' }}" type="text"
                        name="linkedin" id="linkedin" value="{{ old('linkedin', '') }}">
                    @if ($errors->has('linkedin'))
                        <div class="invalid-feedback">
                            {{ $errors->first('linkedin') }}
                        </div>
                    @endif
                    <span class="help-block">{{ trans('cruds.setting.fields.linkedin_helper') }}</span>
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
