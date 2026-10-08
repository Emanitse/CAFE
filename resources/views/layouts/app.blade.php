<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cafe Ordering System</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .sidebar {
            width: 260px;
            flex-shrink: 0;
            background: #66402E;
            min-height: 100vh;
            transition: width 0.3s ease;
            overflow: hidden;
        }
        .sidebar.collapsed { width: 72px; }
        .sidebar.collapsed .nav-text { display: none; }
        .sidebar.collapsed .nav-link { justify-content: center; padding: 0.75rem 0; }
        .sidebar.collapsed hr { display: none; }
        .sidebar.collapsed .text-center { display: none; }
        .sidebar.collapsed .d-flex.justify-content-center.mb-4.mt-4 { margin: 1rem 0 !important; }
        
        .main-content { flex: 1; min-height: 100vh; background: #f8f9fa; }
        
        @media (max-width: 767.98px) {
            .sidebar { position: fixed; left: 0; top: 0; z-index: 1050; transform: translateX(-100%); }
            .sidebar.mobile-open { transform: translateX(0); }
            .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 1040; }
            .sidebar-overlay.show { display: block; }
        }
    </style>
</head>
<body>
    <!-- Mobile header -->
    <div class="d-md-none p-3 d-flex align-items-center shadow-sm" style="background:#5C4033;">
        <button class="btn text-white me-3 p-1" id="mobileToggle"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 5h16"/><path d="M4 12h16"/><path d="M4 19h16"/></svg></button>
        <span class="text-white fw-bold fs-5">Menu</span>
    </div>
    <div class="sidebar-overlay d-md-none" id="overlay"></div>

    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar p-3 d-flex flex-column" id="sidebar">
            <div class="d-none d-md-flex justify-content-end mb-3">
                <button class="btn text-white p-1" id="pcToggle"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 5h16"/><path d="M4 12h16"/><path d="M4 19h16"/></svg></button>
            </div>
            <div class="d-flex justify-content-center mb-4 mt-4 nav-text"><div class="bg-white rounded-circle d-flex align-items-center justify-content-center shadow" style="width:50px;height:50px"><img src="{{ asset('images/cofee-bean.png') }}" class="img-fluid p-1" style="max-width:100%"></div></div>
            <h5 class="text-center mb-5 text-white fw-bold nav-text">Cafe Ordering System</h5>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item mb-1"><a href="/home" class="nav-link fw-bold text-white d-flex align-items-center"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-3"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg><span class="nav-text">Home</span></a></li>
                <li class="nav-item mb-1"><a href="/orders" class="nav-link fw-bold text-white d-flex align-items-center"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-3"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg><span class="nav-text">Orders</span></a></li>
                <li class="nav-item mb-1"><a href="/menu" class="nav-link fw-bold text-white d-flex align-items-center"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-3"><path d="M17 8h1a4 4 0 1 1 0 8h-1"/><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"/><line x1="6" x2="6" y1="2" y2="4"/><line x1="10" x2="10" y1="2" y2="4"/><line x1="14" x2="14" y1="2" y2="4"/></svg><span class="nav-text">Menu</span></a></li>
                <li class="nav-item mb-1"><a href="/categories" class="nav-link fw-bold text-white d-flex align-items-center"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-3"><path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"/><path d="M7 7h.01"/></svg><span class="nav-text">Categories</span></a></li>
                <li class="nav-item mb-1"><a href="/payments" class="nav-link fw-bold text-white d-flex align-items-center"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-3"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg><span class="nav-text">Payments</span></a></li>
                <li class="nav-item mb-1"><a href="/reports" class="nav-link fw-bold text-white d-flex align-items-center"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-3"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg><span class="nav-text">Reports</span></a></li>
                <li class="nav-item mb-1"><a href="/users" class="nav-link fw-bold text-white d-flex align-items-center"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-3"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg><span class="nav-text">Users</span></a></li>
            </ul>
            <hr class="text-white border-2 opacity-50 nav-text">
            <div class="mt-2"><a href="{{ route('logout') }}" class="nav-link text-white fw-bold d-flex align-items-center justify-content-center"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-3"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg><span class="nav-text">Logout</span></a></div>
        </div>

        <!-- Main content -->
        <div class="main-content p-4 overflow-auto">@yield('content')</div>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        const pcToggle = document.getElementById('pcToggle');
        const mobileToggle = document.getElementById('mobileToggle');
        const overlay = document.getElementById('overlay');
        let collapsed = localStorage.getItem('sidebar') === 'collapsed';

        function apply() {
            sidebar.classList.toggle('collapsed', collapsed);
            if (window.innerWidth < 768) sidebar.classList.remove('mobile-open');
        }
        apply();

        pcToggle?.addEventListener('click', () => {
            collapsed = !collapsed;
            localStorage.setItem('sidebar', collapsed ? 'collapsed' : 'expanded');
            apply();
        });

        mobileToggle?.addEventListener('click', () => {
            sidebar.classList.add('mobile-open');
            overlay.classList.add('show');
        });
        overlay?.addEventListener('click', () => {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('show');
        });
        document.querySelectorAll('#sidebar .nav-link').forEach(a => a.addEventListener('click', () => {
            if (window.innerWidth < 768) { sidebar.classList.remove('mobile-open'); overlay.classList.remove('show'); }
        }));
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>