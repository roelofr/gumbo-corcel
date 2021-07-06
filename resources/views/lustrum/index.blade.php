@extends('layout.main')

@php
// Set the metadata
SEOMeta::setTitle($page->title);
SEOMeta::setCanonical($page->url);
@endphp

@section('content')
{{-- All in an article --}}
<div class="container my-16 grid grid-cols-1 lg:grid-cols-2 gap-8 gap-x-4">
    @for ($i = 0; $i < 10; $i++)
    <article class="relative p-4 rounded-lg shadow">
        <div class="flex items-start">
            <div class="flex-none w-32">
                <img src="{{ mix('images/gumbo-royale.png') }}" alt="" class="absolute h-32 lustrum-item">
            </div>

            <div class="flex-grow">
                <h3 class="font-title text-3xl">Gumbo Royale</h3>
                <p class="leading-large text-2xl">
                    De bruisweken zijn de enige echte start van je studententijd in Zwolle.
                </p>
            </div>
        </div>

        <div class="flex items-start mt-4">
            <div class="w-24 mr-8">
                <div class="p-4 rounded bg-gray-secondary-2 grid grid-cols-2 gap-2 gap-y-4">
                    @icon('qrcode', 'h-4 mx-auto')
                    @icon('beer', 'h-4 mx-auto')
                </div>
            </div>

            <button class="btn btn--brand my-0">
                Klik hier voor gratis tickets!
            </button>
        </div>
    </article>
    @endfor
</div>

<style nonce="{{ csp_nonce() }}">
    .lustrum-item {
        top: -2rem;
        left: -2rem;
    }
</style>
@endsection
