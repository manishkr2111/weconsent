<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<body class="bg-dark-subtle">
    <div class="">
        <div class="row h-100 ">
            <div class="col-2 bg-primary-subtle p-4">
                <div class="">
                    <img src="https://www.weconsent.app/weut.png" alt="Logo" class="" height="100" width="100">
                    <div class="text-danger-emphasis pt-3 px-3"><form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-link text-danger-emphasis p-0">
                                <h5>Logout</h5>
                            </button>
                        </form>
                    </div>
                    <hr>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="{{route('dashboard')}}"><h5>Dashboard</h5></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('users') }}"><h5>Users</h5></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('consentRequests') }}"><h5>Consent Requests</h5></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('blockerdUsers') }}"><h5>Blocked Users</h5></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('subscriptions.index') }}"><h5>Subscriptions</h5></a>
                    </li>
                </ul>
            </div>
            <div class="col-10 p-4">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>
</body>
