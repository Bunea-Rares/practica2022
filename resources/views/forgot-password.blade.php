@if (Session::has('message'))
    <div>
        {{ Session::get('message') }}
    </div>
@endif
<form action="{{ route('forget.password.post') }}" method="POST">
    @csrf
    <div>
        <label for="email_address">E-Mail Address</label>
        <div>
            <input type="text" id="email_address" name="email" required autofocus>
            @if ($errors->has('email'))
                <span>{{ $errors->first('email') }}</span>
            @endif
        </div>
    </div>
    <div>
        <button type="submit">
            Send Password Reset Link
        </button>
    </div>
</form>
