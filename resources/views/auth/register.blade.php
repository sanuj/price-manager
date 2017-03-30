@extends('layouts.web')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-8 offset-md-2 my-3">
                <div class="card">
                    <h4 class="card-header bg-white">Register</h4>
                    <div class="card-block row">
                        <form class="form-horizontal col-12 col-lg-8 offset-2" role="form" method="POST"
                              action="{{ route('register') }}">
                            {{ csrf_field() }}

                            <div class="form-group{{ $errors->has('name') ? ' has-danger' : '' }}">
                                <label for="name" class="form-control-label">Name</label>

                                <input id="name" type="text" class="form-control" name="name"
                                       value="{{ old('name') }}" required autofocus>

                                @if ($errors->has('name'))
                                    <span class="form-control-feedback">
                                        <strong>{{ $errors->first('name') }}</strong>
                                    </span>
                                @endif
                            </div>

                            <div class="form-group{{ $errors->has('company') ? ' has-danger' : '' }}">
                                <label for="company" class="fomr-control-label">Company</label>

                                <input id="company" type="text" class="form-control" name="company"
                                       value="{{ old('company') }}" required>

                                @if ($errors->has('company'))
                                    <span class="form-control-feedback">
                                        <strong>{{ $errors->first('company') }}</strong>
                                    </span>
                                @endif
                            </div>

                            <div class="form-group{{ $errors->has('email') ? ' has-danger' : '' }}">
                                <label for="email" class="form-control-label">E-Mail Address</label>

                                <input id="email" type="email" class="form-control" name="email"
                                       value="{{ old('email') }}" required>

                                @if ($errors->has('email'))
                                    <span class="form-control-feedback">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                @endif
                            </div>

                            <div class="form-group{{ $errors->has('password') ? ' has-danger' : '' }}">
                                <label for="password" class="form-control-label">Password</label>

                                <input id="password" type="password" class="form-control" name="password" required>

                                @if ($errors->has('password'))
                                    <span class="form-control-feedback">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                @endif
                            </div>

                            <div class="form-group">
                                <label for="password-confirm" class="form-control-label">Confirm Password</label>

                                <input id="password-confirm" type="password" class="form-control"
                                       name="password_confirmation" required>
                            </div>

                            <div class="form-group text-center">
                                <button type="submit" class="btn btn-primary">
                                    Register
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
