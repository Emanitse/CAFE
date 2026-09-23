<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cafe Ordering System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
     @vite(['resources/css/app.css'])
</head>
<body>
    <div class="d-flex flex-column flex-md-row vh-100">
        
        <!-- sidebar -->
        <div class="bg-nude p-3 d-flex flex-column col-12 col-md-3 col-xl-2">
            <h4 class="mb-4 text-center border-bottom pb-2 text-brown">Cafe Ordering System</h4>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item">
                    
                    <a href="/home" class="nav-link text-brown"> Home</a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link text-brown">Orders</a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link text-brown">Products</a>
                </li>
            </ul>
            <hr>
            <div>
              
                <a href="#" class="nav-link text-white">Logout</a>
            </div>
        </div>

        <!-- Main content -->
        <div class="grow p-4 bg-light overflow-auto">
            @yield('content')
        </div>
        
    </div>
</body>
</html>