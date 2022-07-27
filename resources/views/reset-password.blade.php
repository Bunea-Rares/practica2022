<form action="{{ route('reset.password.post') }}" method="POST">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div>
        <label for="email_address">E-Mail Address</label>
            <input type="text" id="email_address" name="email" required autofocus>
            @if ($errors->has('email'))
                <span>{{ $errors->first('email') }}</span>
            @endif
    </div>
    <div>
        <label for="password">Password</label>
        <div>
            <input type="password" id="password" name="password" required autofocus>
            @if ($errors->has('password'))
                <span>{{ $errors->first('password') }}</span>
            @endif
        </div>
    </div>
    <div>
        <label for="password-confirm">Confirm Password</label>
        <div>
            <input type="password" id="password-confirm" name="password_confirmation" required autofocus>
            @if ($errors->has('password_confirmation'))
                <span>{{ $errors->first('password_confirmation') }}</span>
            @endif
        </div>
    </div>
    <div>
        <button type="submit">
            Reset Password
        </button>
    </div>
</form>
