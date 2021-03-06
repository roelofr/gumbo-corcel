@extends('layout.main')

@section('title', 'Activity overview - Gumbo Millennium')

@section('content')
<h1>{{ $activity->name }} - Enrollment</h1>

<p>Your enrollment is currently <em>{{ $enrollment->state->title }}</em>.</p>

@if (!$enrollment->state->isStable() && $enrollment->expire)
<div class="my-2 px-4 py-2 bg-red-secondary-1 text-red-primary-3 border rounded border-red-primary-1 inline-block">
    <strong>Let op</strong>: Je inschrijving verloopt over {{ $enrollment->expire->diffForHumans(now(), \Carbon\CarbonInterface::DIFF_ABSOLUTE) }}.
</div>
@endif

@if ($enrollment->state == 'created' && $activity->form !== null)
<p><a href="{{ route('enroll.edit', compact('activity')) }}">Supply enrollment details</a></p>
@elseif ($enrollment->state == 'seeded' && $enrollment->price > 0)
<p><a href="{{ route('payment.start', compact('activity')) }}">Pay {{ Str::price($enrollment->total_price) }} via iDEAL</a></p>
@elsecan('unenroll', $enrollment)
</p><p><a href="{{ route('enroll.delete', compact('activity')) }}">Unenroll</a></p>
@endif
@endsection
