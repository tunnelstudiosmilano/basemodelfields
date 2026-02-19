<div class="message col-md-12">
    @if (isset($errors) && count($errors) > 0)
        <div class="alert alert-danger">
            <p><b>Attention!</b></p>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    @if( Session::has('message'))
        <div class="alert alert-success">
            <p>
                {{Session::get('message')}}
            </p>
        </div>
    @endif
    @if( Session::has('success'))
        <div class="alert alert-success ok">
            <p>
                Saved successfully
            </p>
        </div>
    @endif
</div>
