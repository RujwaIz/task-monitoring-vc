@extends('layouts.app')

@section('title', 'Sign in')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">Operations / Sign in</p>
            <h1>Sign in</h1>
        </div>
        <p class="heading-note">Training accounts only. Fake data.</p>
    </div>

    <div class="content-grid">
        <section class="form-panel" aria-labelledby="login-heading">
            <h2 id="login-heading">Your account</h2>
            <p class="form-intro">Use one of the training logins from the participant guide.</p>

            @if ($errors->any())
                <ul class="error-list" role="alert">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            <form action="{{ route('login') }}" method="POST">
                @csrf
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required>
                </div>
                <button class="submit-button" type="submit">Sign in</button>
            </form>
        </section>
    </div>
@endsection
