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
@php
    $consentType = [
        'date'       => "Date",
        'connection' => "Connection",
        'intimate'   => "Intimacy"
    ];
@endphp
<div class="container-fluid py-1">

    <!-- Search Form -->
    <!-- Search Form -->
    <form method="GET" action="{{ route('consentRequests') }}" class="mb-3 d-flex align-items-center">
        <div class="input-group" style="max-width: 90vh;">
            <input type="text" name="search" class="form-control"
                placeholder="Search consent requests by Name/Email" value="{{ $search ?? '' }}">
            <button class="btn btn-primary" type="submit">Search</button>
            @if(!empty($search))
            <a href="{{ route('consentRequests') }}" class="btn btn-secondary ms-2">Clear Filter</a>
            @endif
        </div>
    </form>


    <!-- Card Wrapper -->
    <div class="card shadow-sm" style="border-radius: 20px;">
        <div class="card-body p-0">

            <!-- Responsive Table with Scroll -->
            <div class="table-responsive" style="border-radius: 20px; max-height: 75vh; overflow-y: auto;">
                <table class="table table-hover table-bordered mb-0 align-middle">
                    <thead class="table-primary" style="position: sticky; top: 0; z-index: 10;">
                        <tr>
                            <th scope="col" data-sort="number">S.No</th> <!-- Serial Number -->
                            <th scope="col" data-sort="string">Created By</th>
                            <th scope="col" data-sort="string">Sent To</th>
                            <th scope="col" data-sort="string">Consent Type</th>
                            <!-- <th scope="col" data-sort="string">Date / Intimacy Type</th> -->
                            <th scope="col">Description</th>
                            <th scope="col">Status</th>
                            <th scope="col" data-sort="date">Created At</th>
                            <th scope="col" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($consent_requests as $consent_request)
                        <tr>
                            <td>{{ $loop->iteration + ($consent_requests->firstItem() - 1) }}</td> <!-- Serial Number -->
                            <td>{{ $consent_request->createdBy->name ?? 'N/A' }}</td>
                            <td>{{ $consent_request->sentTo->name ?? 'N/A' }}</td>
                            <td>{{ $consentType[$consent_request->consent_type] }}</td>
                            <!-- <td>{{ $consent_request->date_type ?? $consent_request->intimacy_type }}</td> -->
                            <td>{{ $consent_request->other_type_description ? $consent_request->other_type_description : '-' }}</td>
                            <td>
                                <span class="badge 
                                        @if($consent_request->status === 'approved') bg-success
                                        @elseif($consent_request->status === 'pending') bg-warning text-dark
                                        @elseif($consent_request->status === 'rejected') bg-danger
                                        @else bg-secondary @endif
                                        px-3 py-2 rounded-pill">
                                    {{ ucfirst($consent_request->status) }}
                                </span>
                            </td>
                            <td>{{ $consent_request->created_at->format('Y-m-d H:i') }}</td>
                            <td class="text-center">
                                <a href="{{ route('consentRequests.editOrUpdate', $consent_request->id) }}"
                                    class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                    View
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-4"></i> <br>
                                No consent requests found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

        <!-- Card Footer with Pagination -->
        <div class="card-footer bg-light border-0 d-flex justify-content-center">
            {{ $consent_requests->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const getCellValue = (tr, idx, type) => {
            const val = tr.children[idx].innerText || tr.children[idx].textContent;
            if (type === 'date') return new Date(val);
            if (type === 'number') return parseInt(val, 10) || 0;
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

                // Toggle sort arrow
                th.parentNode.querySelectorAll('th').forEach(th2 => th2.classList.remove('asc', 'desc'));
                th.classList.toggle('asc', asc);
                th.classList.toggle('desc', !asc);
            });
        });
    });
</script>

@endsection