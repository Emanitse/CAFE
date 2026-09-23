<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cafe Ordering System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid vh-100">
        <div class="row h-100">
            
            <!---logos and stuff panel -->
            <div class="col-md-6 bg-dark text-white d-flex flex-column justify-content-center align-items-center">
                <h1 class="display-4 fw-bold">Cafe Name</h1>
                <p class="lead">Staff Ordering System</p>
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
</body>
</html>