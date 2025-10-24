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
<div class="container-fluid py-1">

    <!-- Search Form -->
    <form method="GET" action="{{ route('users') }}" class="mb-3">
        <div class="input-group">
            <input type="text" name="search" class="form-control" style="max-width: 90vh;"
                placeholder="Search users by Name or Email" value="{{ $search ?? '' }}">
            <button class="btn btn-primary" type="submit">Search</button>
            @if(!empty($search))
            <a href="{{ route('consentRequests') }}" class="btn btn-secondary ms-2">Clear Filter</a>
            @endif
        </div>
    </form>

    <!-- Card Wrapper -->
    <div class="card shadow-sm" style="border-radius: 20px;">
        <div class="card-body p-0">

            <!-- Responsive Table -->
            <div class="table-responsive" style="border-radius: 20px; max-height: 75vh; overflow-y: auto;">
                <table class="table table-hover table-bordered mb-0 align-middle">
                    <thead class="table-primary" style="position: sticky; top: 0; z-index: 10;">
                        <tr>
                            <th scope="col">S.No</th>
                            <th scope="col" data-sort="string">Name</th>
                            <th scope="col" data-sort="string">Email</th>
                            <th scope="col" data-sort="date">Registered At</th>
                            <th scope="col" data-sort="string">Status</th>
                            <th scope="col" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                        <tr>
                            <td>{{ $loop->iteration + ($users->firstItem() - 1) }}</td> <!-- Serial Number -->
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->created_at->format('Y-m-d H:i') }}</td>
                            <td>{{ $user->status }}</td>
                            <td class="text-center">
                                <a href="{{ route('users.editOrUpdate', $user->id) }}"
                                    class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                    View
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-4"></i> <br>
                                No users found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

        <!-- Card Footer with Pagination -->
        <div class="card-footer bg-light border-0 d-flex justify-content-center">
            {{ $users->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const getCellValue = (tr, idx, type) => {
        const val = tr.children[idx].innerText || tr.children[idx].textContent;
        if(type === 'date') return new Date(val);
        return val.toLowerCase();
    };

    const comparer = (idx, type, asc) => (a, b) => {
        const v1 = getCellValue(a, idx, type);
        const v2 = getCellValue(b, idx, type);
        if(v1 > v2) return asc ? 1 : -1;
        if(v1 < v2) return asc ? -1 : 1;
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
