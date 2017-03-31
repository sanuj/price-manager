@extends('layouts.web')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-12 col-md-8 offset-md-2 my-3">
                <div class="card">
                    <h4 class="card-header">Reset Password</h4>
                    <div class="card-block row">
                        <div class="col-12 col-lg-8 offset-lg-2">
                            @if (session('status'))
                                <div class="alert alert-success">
                                    {{ session('status') }}
                                </div>
                            @endif

                            <form class="form-horizontal" role="form" method="POST"
                                  action="{{ route('password.email') }}">
                                {{ csrf_field() }}

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

                                <div class="form-group text-center">
                                    <button type="submit" class="btn btn-primary">
                                        Send Password Reset Link
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
