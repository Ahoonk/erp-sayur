@if (session('success'))
    <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">{{ session('error') }}</div>
@endif
@if (session('warning'))
    <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800">{{ session('warning') }}</div>
@endif

@if ($errors->any())
    <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
        <ul class="list-disc ps-5 space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
