@extends('layout.auth')

@section('content')
<section class="module">
    <div class="container">
        <div class="row">
            <div class="col-md-4 m-auto">
                {{-- Show logo --}}
                @include('auth.items.logo')

                {{-- Login form --}}
                <form class="up-form" method="post">
                    <div class="form-group">
                        <input class="form-control form-control-lg" type="email" placeholder="Email">
                    </div>
                    <div class="form-group">
                        <button class="btn btn-block btn-lg btn-round btn-brand" type="submit">Reset password</button>
                    </div>
                </form>

                {{-- Login actions --}}
                <div class="up-help">
                    <p><a href="/auth">Back to Log in page</a></p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection