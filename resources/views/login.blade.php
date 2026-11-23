@extends('layouts.guest')

@section('content')
<div class="container-fluid vh-100">
    <div class="row h-100">
        
        <!---logos and stuff panel -->
        <div class="col-md-6 bg-white d-flex flex-column justify-content-center align-items-center">
            <img src="{{ asset('images/cofee-bean.png') }}" alt="Image" style="max-width: 70%; height: auto;">
            <h1 class="display-4 fw-bold text-brown">Cafe Ordering System</h1>
            
        </div>

        <!-- Auth form panel -->
        <div class="col-md-6 d-flex justify-content-center align-items-center bg-white">
            <div class="card shadow-lg border-0 w-75">
                <div class="card-body p-5">
                    <!-- Success toast for registration -->
                    @if(session('success'))
                        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;">
                            <div id="successToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
                                <div class="d-flex">
                                    <div class="toast-body">
                                        {{ session('success') }}
                                    </div>
                                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Login Form -->
                    <div id="loginForm" class="auth-form {{ !($showRegister ?? false) ? '' : 'd-none' }}">
                        <h3 class="mb-4 fw-bold text-center">Sign In</h3>

                        <!-- Form-level error alert (for lockout, invalid credentials, etc.) -->
                        @if($errors->has('email') || session('error'))
                            <div class="alert alert-danger mb-4" id="errorAlert">
                                @if($errors->has('email'))
                                    <span id="errorMessage">{{ $errors->first('email') }}</span>
                                @else
                                    <span id="errorMessage">{{ session('error') }}</span>
                                @endif
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                       id="email" name="email" value="{{ old('email') }}" required autocomplete="email">
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label">Password <small class="text-muted">(min. 8 characters)</small></label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                       id="password" name="password" required autocomplete="current-password">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn bg-coffee text-white w-100 py-2 rounded" id="loginBtn">Login</button>
                        </form>

                        <div class="text-center mt-3">
                            <p class="text-muted mb-0">No account yet? <a href="{{ route('register') }}" class="text-brown fw-bold">Register</a></p>
                        </div>
                    </div>

                    <!-- Register Form -->
                    <div id="registerForm" class="auth-form {{ ($showRegister ?? false) ? '' : 'd-none' }}">
                        <h3 class="mb-4 fw-bold text-center">Create Account</h3>

                        @if($errors->any())
                            <div class="alert alert-danger mb-4">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('register.store') }}">
                            @csrf
                            <div class="mb-3">
                                <label for="username" class="form-label">Username <small class="text-muted">(display name)</small></label>
                                <input type="text" class="form-control @error('username') is-invalid @enderror" 
                                       id="username" name="username" value="{{ old('username') }}" required autocomplete="username">
                                @error('username')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
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
                                       id="password" name="password" required autocomplete="new-password">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="password_confirmation" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" 
                                       id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                                @error('password_confirmation')
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
                            <button type="submit" class="btn bg-coffee text-white w-100 py-2 rounded">Register</button>
                        </form>

                        <div class="text-center mt-3">
                            <p class="text-muted mb-0">Already have an account? <a href="{{ route('login') }}" class="text-brown fw-bold">Sign In</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
  
    document.addEventListener('DOMContentLoaded', function() {
        const toastEl = document.getElementById('successToast');
        if (toastEl) {
            const toast = new bootstrap.Toast(toastEl);
            toast.show();
        }


        initLockoutCountdown();
    });

    function initLockoutCountdown() {
     
        const lockoutEndTime = {{ session('lockout_end_time', 0) }};
        
        if (!lockoutEndTime || lockoutEndTime <= 0) {
            return;
        }

        const errorAlert = document.getElementById('errorAlert');
        const errorMessage = document.getElementById('errorMessage');
        const loginBtn = document.getElementById('loginBtn');
        
        if (!errorAlert || !errorMessage) {
            return;
        }

    
        if (loginBtn) {
            loginBtn.disabled = true;
        }

        function updateCountdown() {
            const now = Math.floor(Date.now() / 1000);
            const remaining = Math.max(0, lockoutEndTime - now);
            
            if (remaining > 0) {
                errorMessage.textContent = `Too many failed attempts. Please try again in ${remaining} seconds.`;
            } else {
                errorMessage.textContent = 'You can now try logging in again.';
                errorAlert.classList.remove('alert-danger');
                errorAlert.classList.add('alert-info');
                
                if (loginBtn) {
                    loginBtn.disabled = false;
                }
                
              
                fetch('/login', { method: 'GET', credentials: 'same-origin' })
                    .then(() => {
                      
                        window.location.reload();
                    });
                
                return;
            }
        }

        
        updateCountdown();
        
        
        const interval = setInterval(updateCountdown, 1000);
        
       
        window.addEventListener('beforeunload', () => clearInterval(interval));
    }
</script>
@endpush