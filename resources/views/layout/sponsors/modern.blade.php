@php
$sponsorClass = Str::slug("sponsor--brand-{$sponsor->slug}");
$backdrop = image_asset($sponsor->background_image)->height(1920)->with(960)->fit('crop');
$logo = $sponsorService->toSvg($sponsor, [
    'class' => 'sponsor__card-logo-img',
    'title' => $sponsor->name,
    'aria-label' => "Logo van {$sponsor->name}"
], 'color');
@endphp
{{-- Style for the sponsor --}}
<style nonce="{{ csp_nonce() }}">
.sponsor--backdrop-brand {
    background-image: url('{{ $image }}');
}
</style>

{{-- Actual sponsor --}}
<div class="sponsor sponsor--backdrop sponsor--backdrop-brand">
    <div class="container sponsor__container sponsor__container--modern">
        <div class="sponsor__card">
            {{-- Image --}}
            <figure class="sponsor__card-logo">
                {{ $logo }}
            </figure>

            {{-- Text --}}
            <p class="sponsor__card-text">
                {{ Str::words($sponsor->caption, 40) }}
            </p>

            {{-- Button --}}
            <a href="{{ route('sponsors.link', compact('sponsor')) }}" target="_blank" rel="noopener" class="btn btn--brand sponsor__card-btn">
                Bekijk sponsor
            </a>
        </div>
    </div>
</div>
