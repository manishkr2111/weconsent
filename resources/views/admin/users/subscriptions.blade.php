@extends('layouts.admin')

@section('content')
<style>
    :root {
        --primary-color: #3C1D71;
        --primary-hover: #2e1457;
        --secondary-color: #ffffff;
    }

    h4 {
        color: var(--primary-color);
        font-weight: 600;
    }

    p {
        color: #555;
    }

    th {
        background-color: var(--primary-color);
        color: var(--secondary-color);
        cursor: pointer;
    }

    th.asc::after {
        content: " ▲";
    }

    th.desc::after {
        content: " ▼";
    }

    .btn-primary {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .btn-primary:hover {
        background-color: var(--primary-hover);
        border-color: var(--primary-hover);
    }

    .table-hover tbody tr:hover {
        background-color: rgba(60, 29, 113, 0.1);
    }

    .card {
        border-radius: 20px;
        border: 1px solid rgba(60, 29, 113, 0.2);
    }

    #searchInput {
        border: 1px solid var(--primary-color);
    }

    .page-item.active .page-link {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        color: var(--secondary-color);
    }
</style>

<div class="container-fluid py-2">
    <h4>Active Subscriptions</h4>
    <p>(Current active subscription of users)</p>

    <!-- Search -->
    <div class="mb-3">
        <input type="text" id="searchInput" class="form-control" style="max-width: 90vh;" placeholder="Search users by Name or Email">
    </div>

    <!-- Card -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive" style="border-radius: 20px; max-height: 75vh; overflow-y: auto;">
                <div class="card-header d-flex justify-content-between align-items-center" style="background-color:#3C1D71; color:white;">
                    <span>Active Subscriptions</span>
                </div>
                <table class="table table-hover table-bordered mb-0 align-middle">
                    <thead class="table-primary" style="position: sticky; top: 0; z-index: 10;">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col" data-sort="string">Name</th>
                            <th scope="col" data-sort="string">Email</th>
                            <th scope="col" data-sort="string">Subscription Status</th>
                            <th scope="col" data-sort="date">Start Date</th>
                            <th scope="col" data-sort="date">End Date</th>
                            <th scope="col" data-sort="string">Subscription ID</th>
                            <th scope="col" data-sort="string">Stripe Customer ID</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody">
                        @forelse($users as $index => $user)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><a href="{{ route('users.editOrUpdate', $user->id) }}">{{ $user->name ?? 'N/A' }}</a></td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->detail->subscription_status ?? 'N/A' }}</td>
                            <td>{{ $user->detail->subscription_start_date ?? 'N/A' }}</td>
                            <td>{{ $user->detail->subscription_end_date ?? 'N/A' }}</td>
                            <td>
                                @if($user->detail->subscription_id)
                                <span id="sub-{{ $user->id }}" data-full="{{ $user->detail->subscription_id }}">
                                    {{ substr($user->detail->subscription_id, 0, 4) }}****{{ substr($user->detail->subscription_id, -4) }}
                                </span>
                                <button type="button" class="btn btn-sm btn-outline-secondary p-1 ms-1" onclick="copyToClipboard('sub-{{ $user->id }}')" title="Copy">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                                @else N/A @endif
                            </td>
                            <td>
                                @if($user->detail->stripe_customer_id)
                                <span id="cust-{{ $user->id }}" data-full="{{ $user->detail->stripe_customer_id }}">
                                    {{ substr($user->detail->stripe_customer_id, 0, 4) }}****{{ substr($user->detail->stripe_customer_id, -4) }}
                                </span>
                                <button type="button" class="btn btn-sm btn-outline-secondary p-1 ms-1" onclick="copyToClipboard('cust-{{ $user->id }}')" title="Copy">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                                @else N/A @endif
                            </td>
                            <td>
                                <a href="{{ route('users.editOrUpdate', $user) }}" class="btn btn-sm btn-primary rounded-pill">
                                    Edit
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-4"></i><br>No subscription details found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Pagination -->
        <div class="mt-3 d-flex justify-content-center">
            {{ $users->links('pagination::bootstrap-5') }}
        </div>

    </div>
</div>

<script>
    function copyToClipboard(elementId) {
        const element = document.getElementById(elementId);
        const text = element.getAttribute('data-full');
        navigator.clipboard.writeText(text)
            .then(() => {
                element.setAttribute('title', 'Copied!');
                setTimeout(() => element.setAttribute('title', 'Copy'), 1500);
            })
            .catch(err => console.error('Failed to copy: ', err));
    }

    document.getElementById('searchInput').addEventListener('input', function() {
        const filter = this.value.toLowerCase();
        document.querySelectorAll('#userTableBody tr').forEach(row => {
            const name = row.children[1].innerText.toLowerCase();
            const email = row.children[2].innerText.toLowerCase();
            row.style.display = (name.includes(filter) || email.includes(filter)) ? '' : 'none';
        });
        setupPagination();
    });

    document.addEventListener('DOMContentLoaded', () => {
        const getCellValue = (tr, idx, type) => {
            const val = tr.children[idx].innerText || tr.children[idx].textContent;
            if (type === 'date') return new Date(val) || 0;
            return val.toLowerCase();
        };

        const comparer = (idx, type, asc) => (a, b) => {
            const v1 = getCellValue(a, idx, type);
            const v2 = getCellValue(b, idx, type);
            return (v1 > v2 ? 1 : v1 < v2 ? -1 : 0) * (asc ? 1 : -1);
        };

        document.querySelectorAll('th[data-sort]').forEach(th => {
            th.addEventListener('click', () => {
                const table = th.closest('table');
                const tbody = table.querySelector('tbody');
                const type = th.dataset.sort;
                const idx = Array.from(th.parentNode.children).indexOf(th);
                const asc = !th.classList.contains('asc');

                Array.from(tbody.querySelectorAll('tr'))
                    .sort(comparer(idx, type, asc))
                    .forEach(tr => tbody.appendChild(tr));

                th.parentNode.querySelectorAll('th').forEach(th2 => th2.classList.remove('asc', 'desc'));
                th.classList.toggle('asc', asc);
                th.classList.toggle('desc', !asc);

                setupPagination();
            });
        });

        setupPagination();
    });

    const rowsPerPage = 10;
    let currentPage = 1;

    function setupPagination() {
        const tableRows = Array.from(document.querySelectorAll('#userTableBody tr')).filter(row => row.style.display !== 'none');
        const totalPages = Math.ceil(tableRows.length / rowsPerPage);
        currentPage = Math.min(currentPage, totalPages) || 1;

        tableRows.forEach((row, i) => row.style.display = 'none');

        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        tableRows.slice(start, end).forEach(row => row.style.display = '');

        const pagination = document.getElementById('pagination');
        if (pagination) {
            pagination.innerHTML = '';
            for (let i = 1; i <= totalPages; i++) {
                const li = document.createElement('li');
                li.className = `page-item ${i === currentPage ? 'active' : ''}`;
                li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
                li.addEventListener('click', (e) => {
                    e.preventDefault();
                    currentPage = i;
                    setupPagination();
                });
                pagination.appendChild(li);
            }
        }
    }
</script>
@endsection