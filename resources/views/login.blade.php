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
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class=" bg-brown text-white w-100 py-2 rounded">Login</button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
    @endsection

