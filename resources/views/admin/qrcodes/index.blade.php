@extends('layouts.admin')

@section('content')
<div class="container mx-auto p-4">
    <h4 class="text-2xl font-bold mb-4" style="color: #3C1D71;">QR Codes</h4>

    <!-- 🔍 Search Input -->
    <div class="mb-4">
        <input 
            type="text" 
            id="searchInput" 
            placeholder="Search by name or email..." 
            class="w-full md:w-1/3 px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500"
        >
    </div>

    <table id="qrTable" class="min-w-full bg-white border border-gray-200">
        <thead style="background-color: #3C1D71; color: white;">
            <tr>
                <th class="border px-4 py-2">Sr No</th>
                <th class="border px-4 py-2">User Name</th>
                <th class="border px-4 py-2">User Email</th>
                <th class="border px-4 py-2">Times Generated</th>
                <th class="border px-4 py-2">QR Code</th>
                <th class="border px-4 py-2">Created At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($qrcodes as $index => $qr)
            <tr>
                <td class="border px-4 py-2">{{ $qrcodes->firstItem() + $index }}</td>
                <td class="border px-4 py-2">
                    <a href="{{ route('users.editOrUpdate', $qr->user_id) }}" class="text-purple-700 hover:underline">
                        {{ $qr->user ? ucfirst($qr->user->name) : 'N/A' }}
                    </a>
                </td>
                <td class="border px-4 py-2">{{ $qr->user ? $qr->user->email : 'N/A' }}</td>
                <td class="border px-4 py-2">{{ $qr->generated_count }}</td>
                <td class="border px-4 py-2">
                    @if($qr->path)
                        <a href="{{ asset('/storage/qrcodes/'.$qr->path) }}" target="_blank" class="text-blue-600 hover:underline">View QR</a>
                    @else
                        N/A
                    @endif
                </td>
                <td class="border px-4 py-2">{{ $qr->created_at->format('Y-m-d H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">
        {{ $qrcodes->links() }} <!-- Pagination -->
    </div>
</div>

<!-- 🔍 Simple JS Search Filter -->
<script>
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        const rows = document.querySelectorAll('#qrTable tbody tr');

        rows.forEach(row => {
            const name = row.cells[1].innerText.toLowerCase();
            const email = row.cells[2].innerText.toLowerCase();
            
            if (name.includes(searchValue) || email.includes(searchValue)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
</script>
@endsection
