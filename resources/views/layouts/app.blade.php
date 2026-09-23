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
    <!-- Mobile Top Bar with Burger Button (Hidden on md and up) -->
    <div class="d-md-none bg-nude p-3 d-flex align-items-center shadow-sm">
        <button class="btn btn-outline-secondary me-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu">
            <!-- hamburger icon -->
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-menu preview-icon"><path d="M4 5h16"/><path d="M4 12h16"/><path d="M4 19h16"/></svg>
        </button>
        
    </div>

    <div class="d-flex flex-column flex-md-row vh-100">
        
        <!-- sidebar -->
        <div class="bg-nude p-3 d-flex flex-column offcanvas-md offcanvas-start col-md-3 col-xl-2" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
            
            <!-- mobile view header -->
            <div class="offcanvas-header d-md-none">
                <h5 class="offcanvas-title text-brown fw-bold" id="sidebarMenuLabel">Cafe Ordering System</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu" aria-label="Close"></button>
            </div>

            <div class="offcanvas-body d-flex flex-column flex-grow-1">
                <div class="d-flex justify-content-center mb-3">
                    <img src="{{ asset('images/cofee-bean.png') }}" alt="Image" style="max-width: 70%; height: auto;">
                </div>
                
                <ul class="nav nav-pills flex-column mb-auto">
                    <li class="nav-item">
                        <a href="/home" class="nav-link fw-bold text-brown">Home</a>
                    </li>
                    <li class="nav-item">
                        <a href="/orders" class="nav-link fw-bold text-brown">Orders</a>
                    </li>
                    <li class="nav-item">
                        <a href="/menu" class="nav-link fw-bold text-brown">Menu</a>
                    </li>
                    <li class="nav-item">
                        <a href="/categories" class="nav-link fw-bold text-brown">Categories</a>
                    </li>
                    <li class="nav-item">
                        <a href="/payments" class="nav-link fw-bold text-brown">Payments</a>
                    </li>
                    <li class="nav-item">
                        <a href="/reports" class="nav-link fw-bold text-brown">Reports</a>
                    </li>
                    <li class="nav-item">
                        <a href="/users" class="nav-link fw-bold text-brown">Users Management</a>
                    </li>

                </ul>
                <hr>
                <div class="text-center">
                    <a href="{{ route('logout') }}" class="nav-link text-brown fw-bold">Logout</a>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <div class="grow p-4 bg-light overflow-auto flex-grow-1">
            @yield('content')
        </div>
        
    </div>

    <!-- Bootstrap JS Bundle (Required for the Offcanvas toggle to work) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>