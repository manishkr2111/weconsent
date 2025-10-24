@extends('layouts.admin')

@section('content')
<style>
    th.asc::after {
        content: " ▲";
    }

    th.desc::after {
        content: " ▼";
    }
</style>
<div class="container-fluid py-2">
    <h4>Subscriptions Details</h4>
    <p>(All created subdcription till now, <a href="{{route('users.subscriptions')}}">click here</a> to see active subscriptions for users)</p>
    <!-- Search Form -->
    <form method="GET" action="{{ route('subscriptions.index') }}" class="mb-3">
        <div class="input-group">
            <input type="text" name="search" class="form-control" style="max-width: 90vh;"
                placeholder="Search subscriptions by ID, Customer or Creator" value="{{ $search ?? '' }}">
            <button class="btn btn-primary" type="submit">Search</button>
            @if($search)
            <a href="{{ route('subscriptions.index') }}" class="btn btn-secondary">Clear Filter</a>
            @endif
        </div>
    </form>

    <!-- Card Wrapper -->
    <div class="card shadow-sm" style="border-radius: 20px;">
        <div class="card-body p-0">
            <div class="table-responsive" style="border-radius: 20px; max-height: 75vh; overflow-y: auto;">
                <table class="table table-hover table-bordered mb-0 align-middle">
                    <thead class="table-primary" style="position: sticky; top: 0; z-index: 10;">
                        <tr>
                            <th scope="col">S.No</th> <!-- New Serial Number Column -->
                            <th scope="col" data-sort="string">Created By</th>
                            <th scope="col">Subscription ID</th>
                            <th scope="col">Customer ID</th>
                            <th scope="col" data-sort="string">Status</th>
                            <th scope="col" data-sort="date">Start Date</th>
                            <th scope="col" data-sort="date">End Date</th>
                            <th scope="col" data-sort="string">Billing Name</th>
                            <th scope="col" data-sort="string">Billing Email</th>
                            <th scope="col" data-sort="date">Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subscriptions as $subscription)
                        <tr>
                            <td>{{ $loop->iteration + ($subscriptions->firstItem() - 1) }}</td> <!-- Serial Number -->
                            <td><a href="{{ route('users.editOrUpdate', $subscription->user_id) }}">{{ $subscription->user->name ?? 'N/A' }}</a></td>
                            <td>
                                <span id="sub-{{ $subscription->id }}" data-full="{{ $subscription->subscription_id }}">
                                    {{ substr($subscription->subscription_id, 0, 6) }}****
                                </span>
                                <button type="button" class="btn btn-sm btn-outline-secondary p-1 ms-1" onclick="copyToClipboard('sub-{{ $subscription->id }}')" title="Copy">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </td>
                            <td>
                                <span id="customer-{{ $subscription->id }}" data-full="{{ $subscription->customer_id }}">
                                    {{ substr($subscription->customer_id, 0, 6) }}****
                                </span>
                                <button type="button" class="btn btn-sm btn-outline-secondary p-1 ms-1" onclick="copyToClipboard('customer-{{ $subscription->id }}')" title="Copy">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </td>

                            <td>{{ ucfirst($subscription->status) }}</td>
                            <td>{{ $subscription->start_date?->format('Y-m-d') }}</td>
                            <td>{{ $subscription->end_date?->format('Y-m-d') }}</td>
                            <td>{{ $subscription->billing_name }}</td>
                            <td>{{ $subscription->billing_email }}</td>
                            <td>{{ $subscription->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-4"></i><br>No subscriptions found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

            </div>
        </div>

        <!-- Pagination -->
        <div class="card-footer bg-light border-0 d-flex justify-content-center">
            {{ $subscriptions->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>



<script>
    function copyToClipboard(elementId) {
        const element = document.getElementById(elementId);
        const text = element.getAttribute('data-full'); // full ID
        navigator.clipboard.writeText(text)
            .then(() => {
                // optional temporary tooltip
                element.setAttribute('title', 'Copied!');
                setTimeout(() => element.setAttribute('title', 'Copy'), 1500);
            })
            .catch(err => console.error('Failed to copy: ', err));
    }

    // Sorting functionality
    document.addEventListener('DOMContentLoaded', () => {
        const getCellValue = (tr, idx, type) => {
            const val = tr.children[idx].innerText || tr.children[idx].textContent;
            if (type === 'date') return new Date(val);
            return val.toLowerCase();
        };

        const comparer = (idx, type, asc) => (a, b) => {
            const v1 = getCellValue(a, idx, type);
            const v2 = getCellValue(b, idx, type);
            if (v1 > v2) return asc ? 1 : -1;
            if (v1 < v2) return asc ? -1 : 1;
            return 0;
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
            });
        });
    });
</script>


@endsection