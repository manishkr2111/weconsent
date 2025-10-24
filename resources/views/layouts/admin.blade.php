<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="icon" type="image/png" href="{{ asset('storage/website/weut.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        body,
        html {
            height: 100%;
            margin: 0;
        }

        .container-flex {
            display: flex;
            flex-wrap: nowrap;
        }

        .sidebar {
            background-color: #e0e0f0;
            min-height: 100vh;
            padding: 1rem;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }

        .sidebar img {
            display: block;
            margin: 0 auto 1rem auto;
        }

        .main-content {
                flex-grow: 1;
                margin-top: 10px;
            }
        @media screen and (max-width: 500px) {
            /* Main content */
            .main-content {
                flex-grow: 1;
                margin-top: 60px;
            }
        }

        /* Large screens: sidebar always visible */
        @media (min-width: 992px) {
            .sidebar {
                width: 220px;
                transform: translateX(0);
            }
        }

        /* Small screens: sidebar hidden by default */
        @media (max-width: 991px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                width: 220px;
                height: 100%;
                z-index: 9999;
                background-color: #e0e0f0;
                transform: translateX(-100%);
                box-shadow: 2px 0 5px rgba(0, 0, 0, 0.3);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .overlay {
                position: fixed;
                display: none;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 998;
            }

            .overlay.show {
                display: block;
            }
        }

        .nav-toggle-btn {
            display: none;
        }

        @media (max-width: 991px) {
            .nav-toggle-btn {
                display: block;
                position: fixed;
                top: 10px;
                left: 10px;
                z-index: 99999;
                background: #fff;
                border: 1px solid #ccc;
                border-radius: 5px;
                padding: 5px 10px;
            }
            .main-container{
                display: block;
            }
        }
        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 8px 12px; /* smaller box */
            border-radius: 6px; /* tighter corners */
            background: #fff;
            color: #3C1D71;
            font-size: 0.9rem; /* slightly smaller text */
            font-weight: 500;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .sidebar-link:hover {
            background: #3C1D71;
            color: #fff;
            transform: translateX(4px);
            box-shadow: 0 3px 8px rgba(60,29,113,0.3);
        }
        
        .sidebar-link i {
            font-size: 1rem; /* smaller icons */
        }
        
        .sidebar-link.active {
            background: #3C1D71;
            color: #fff;
            font-weight: 600;
            box-shadow: 0 3px 8px rgba(60,29,113,0.3);
        }

    </style>
</head>

<body class="bg-light">

    <!-- Toggle Button for Mobile -->
    <button class="nav-toggle-btn" id="sidebarToggle"><i class="bi bi-list fs-4"></i></button>

    <!-- Overlay for mobile -->
    <div class="overlay" id="sidebarOverlay"></div>

    <div class="container-flex main-container">

        <!-- Sidebar -->
        <div class="sidebar" id="sidebar" style="min-width: fit-content; max-width: fit-content;">
            <img src="{{ asset('storage/website/weut.png') }}" alt="Logo" height="80">
            <h5 class="ms-5"><a href="{{ route('admin.profile') }}">{{ ucwords(Auth::user()->name) }}</a></h5>
            <form method="POST" action="{{ route('logout') }}" class="text-center mb-3">
                @csrf
                <button type="submit" class="btn btn-danger btn-sm w-100">Logout</button>
            </form>
            <ul class="nav flex-column gap-2 mt-2">
                <li class="nav-item">
                    <a class="nav-link sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" 
                        href="{{ route('dashboard') }}">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-link {{ request()->routeIs('consentRequests') ? 'active' : '' }}" 
                        href="{{ route('consentRequests') }}">
                        <i class="bi bi-file-earmark-text me-2"></i> Consent Requests
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-link {{ request()->routeIs('users') ? 'active' : '' }}" 
                        href="{{ route('users') }}">
                        <i class="bi bi-people me-2"></i> Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-link {{ request()->routeIs('blockerdUsers') ? 'active' : '' }}" 
                        href="{{ route('blockerdUsers') }}">
                        <i class="bi bi-person-x me-2"></i> Blocked Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-link {{ request()->routeIs('subscriptions.index') ? 'active' : '' }}" 
                        href="{{ route('subscriptions.index') }}">
                        <i class="bi bi-credit-card me-2"></i> Subscriptions
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-link {{ request()->routeIs('qrcodes.index') ? 'active' : '' }}" 
                        href="{{ route('qrcodes.index') }}">
                        <i class="bi bi-qr-code"></i>QR Codes
                    </a>
                </li>
            </ul>
        </div>
        <!-- Main Content -->
        <div class="main-content">
            @yield('content')
        </div>
    </div>



    <script>
        // Custom JS for sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('sidebarToggle');
            const overlay = document.getElementById('sidebarOverlay');

            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
            });

            overlay.addEventListener('click', () => {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            });
        });
    </script>

</body>

</html>