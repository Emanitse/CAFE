@extends('layouts.guest')

@section('content')
    <div class="container-fluid vh-100">
        <div class="row h-100">
            
            <!---logos and stuff panel -->
            <div class="col-md-6 bg-dark text-white d-flex flex-column justify-content-center align-items-center">
                <h1 class="display-4 fw-bold">Cafe Name</h1>
                <p class="lead">Cafe Ordering System</p>
            </div>

            <!-- login form panel -->
            <div class="col-md-6 d-flex justify-content-center align-items-center bg-light">
                <div class="w-50">
                    <h3 class="mb-4 fw-bold">Sign In</h3>

                    <!-- the error message -->
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- login form -->
                    <form action="/login" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2">Login</button>
                    </form>
                </div>
            </div>

        </div>
    </div>
    @endsection

