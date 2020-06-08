@php
$sponsorClass = Str::slug("sponsor--brand-{$sponsor->slug}");
$sponsorBackdrop = Storage::url(\App\Models\Sponsor::IMAGE_DISK, $item->backdrop);
@endphp
@push('main.styles')
<style nonce="{{ csp_nonce() }}">
.sponsor--backdrop-brand {
    background-image: url("{{ $sponsorBackdrop }}");
}
</style>
@endpush
<div class="sponsor sponsor--backdrop sponsor--backdrop-brand">
    <div class="container sponsor__container">
        <a href="{{ route('sponsors.link', compact('sponsor')) }}" class="sponsor__simple-link">
            <img src="{{ $sponsor->logo_color_url }}" alt="{{ $sponsor->title }}" class="sponsor__simple-logo">
        </a>
    </div>
</div>