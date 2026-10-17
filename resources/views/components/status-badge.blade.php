{{-- Badge status konsisten (fallback biru untuk nilai tak dikenal). --}}
@php
$s = strtolower((string) ($status ?? ''));
$map = [
    'draft' => 'bg-secondary', 'void' => 'bg-secondary', 'closed' => 'bg-secondary',
    'open' => 'bg-blue', 'active' => 'bg-blue',
    'pending' => 'bg-yellow', 'submitted' => 'bg-yellow', 'scheduled' => 'bg-yellow',
    'reserved' => 'bg-yellow', 'partial' => 'bg-yellow', 'unpaid' => 'bg-yellow',
    'approved' => 'bg-green', 'posted' => 'bg-green', 'paid' => 'bg-green',
    'matched' => 'bg-green', 'released' => 'bg-green', 'done' => 'bg-green',
    'overdue' => 'bg-red', 'rejected' => 'bg-red', 'cancelled' => 'bg-red',
    'canceled' => 'bg-red', 'failed' => 'bg-red', 'hold' => 'bg-red',
];
$cls = $map[$s] ?? 'bg-blue';
@endphp
<span class="badge {{ $cls }}">{{ $status }}</span>
