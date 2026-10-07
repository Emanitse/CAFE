<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cafe Ordering System</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <!-- Mobile Top Bar with Burger Button (Hidden on md and up) -->
    <div class="d-md-none p-3 d-flex align-items-center shadow-sm" style="background-color: #5C4033;">
        <button class="btn text-white me-3 p-1" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu">
            <!-- hamburger icon -->
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-menu preview-icon"><path d="M4 5h16"/><path d="M4 12h16"/><path d="M4 19h16"/></svg>
        </button>
        <span class="text-white fw-bold fs-5">Menu</span>
    </div>

    <div class="d-flex flex-column flex-md-row vh-100">
        
        <!-- sidebar (Grid classes removed, defaulting to pc-expanded) -->
        <div class="bg-coffee p-3 d-flex flex-column offcanvas-md offcanvas-start pc-expanded" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
            
            <!-- mobile view header -->
            <div class="offcanvas-header d-md-none border-bottom border-secondary mb-3">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu" aria-label="Close"></button>
            </div>

            <div class="offcanvas-body d-flex flex-column flex-grow-1">
                
                <!-- PC View Burger Button (Hidden on mobile) -->
                <div class="d-none d-md-flex justify-content-end w-100">
                    <button class="btn text-white p-1 shadow-none" type="button" id="pcSidebarToggle">
                        <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16"/><path d="M4 12h16"/><path d="M4 19h16"/></svg>
                    </button>
                </div>

                <!-- Logo with White Circle Badge -->
                <div class="d-flex justify-content-center mb-4 mt-4 nav-text">
                    <div class="bg-white rounded-circle d-flex justify-content-center align-items-center shadow" style="width: 50px; height: 50px; overflow: hidden;">
                        <img src="{{ asset('images/cofee-bean.png') }}" alt="Logo" class="img-fluid p-1" style="max-width: 100%; height: auto;">
                    </div>
                </div>
                <h5 class="text-center mb-5 text-white fw-bold nav-text">Cafe Ordering System</h5>
                
                <ul class="nav nav-pills flex-column mb-auto">
                    <li class="nav-item mb-1">
                        <a href="/home" class="nav-link fw-bold text-white d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-3"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                            <span class="nav-text">Home</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a href="/orders" class="nav-link fw-bold text-white d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-3"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                            <span class="nav-text">Orders</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a href="/menu" class="nav-link fw-bold text-white d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-3"><path d="M17 8h1a4 4 0 1 1 0 8h-1"/><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"/><line x1="6" x2="6" y1="2" y2="4"/><line x1="10" x2="10" y1="2" y2="4"/><line x1="14" x2="14" y1="2" y2="4"/></svg>
                            <span class="nav-text">Menu</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a href="/categories" class="nav-link fw-bold text-white d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-3"><path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"/><path d="M7 7h.01"/></svg>
                            <span class="nav-text">Categories</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a href="/payments" class="nav-link fw-bold text-white d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-3"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                            <span class="nav-text">Payments</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a href="/reports" class="nav-link fw-bold text-white d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-3"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                            <span class="nav-text">Reports</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a href="/users" class="nav-link fw-bold text-white d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-3"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            <span class="nav-text">Users Management</span>
                        </a>
                    </li>
                </ul>
                
                <hr class="text-white border-2 opacity-50 nav-text">
                <div class="mt-2">
                    <a href="{{ route('logout') }}" class="nav-link text-white fw-bold d-flex align-items-center justify-content-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-3"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                        <span class="nav-text">Logout</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Main content (Default margin added to match the expanded overlay) -->
        <div id="mainContent" class="grow p-4 bg-light overflow-auto flex-grow-1" style="transition: margin-left 0.3s ease; margin-left: 120px;">
            @yield('content')
        </div>
        
    </div>

   

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('pcSidebarToggle');
            const sidebar = document.getElementById('sidebarMenu');
            const mainContent = document.getElementById('mainContent'); 
            const navTexts = document.querySelectorAll('.nav-text');
            
            // 1. Check browser memory for saved state (true if collapsed, false if expanded)
            let isCollapsed = localStorage.getItem('sidebarState') === 'collapsed';

            // 2. Create a clean function to apply the visual changes
            function applySidebarState() {
    if (isCollapsed) {
        sidebar.classList.remove('pc-expanded');
        sidebar.classList.add('pc-collapsed');
        mainContent.style.marginLeft = '0'; 
    } else {
        sidebar.classList.remove('pc-collapsed');
        sidebar.classList.add('pc-expanded');
        mainContent.style.marginLeft = '120px'; 
    }
}

            // 3. Immediately apply the correct state when the page loads
            applySidebarState();

            // 4. Handle the button click
            toggleBtn.addEventListener('click', function() {
                isCollapsed = !isCollapsed;
                
                // Save the user's new preference to browser memory
                localStorage.setItem('sidebarState', isCollapsed ? 'collapsed' : 'expanded');
                
                applySidebarState();
            });
        });
    </script>

   
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>