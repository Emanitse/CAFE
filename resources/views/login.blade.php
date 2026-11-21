@extends('layouts.guest')

@section('content')
<div class="container-fluid vh-100">
    <div class="row h-100">
        
        <!---logos and stuff panel -->
        <div class="col-md-6 bg-white d-flex flex-column justify-content-center align-items-center">
            <img src="{{ asset('images/cofee-bean.png') }}" alt="Image" style="max-width: 70%; height: auto;">
            <h1 class="display-4 fw-bold text-brown">Cafe Ordering System</h1>
            
        </div>

        <!-- login form panel -->
        <div class="col-md-6 d-flex justify-content-center align-items-center bg-white">
            <div class="card shadow-lg border-0 w-75">
                <div class="card-body p-5">
                    <h3 class="mb-4 fw-bold text-center">Sign In</h3>

                    <!-- the error message -->
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- login form -->
                    <form method="POST" action="/login">
                        @csrf
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                   id="email" name="email" value="{{ old('email') }}" required autocomplete="email">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password <small class="text-muted">(min. 8 characters)</small></label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                   id="password" name="password" required autocomplete="current-password">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-4">
                            <label for="captcha" class="form-label">CAPTCHA: {{ session('captcha_question', '?') }}</label>
                            <input type="number" class="form-control @error('captcha') is-invalid @enderror" 
                                   id="captcha" name="captcha" required autocomplete="off" 
                                   placeholder="Enter the answer">
                            @error('captcha')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn bg-coffee text-white w-100 py-2 rounded">Login</button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection