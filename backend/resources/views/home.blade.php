@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">

            <div class="card">
                <div class="card-header">{{ __('Profile') }}</div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-9">
                            <span id="loghy_button_container"></span>
                        </div>
                        <div class="col-md-3">
                            <form method="post" action="{{ route('loghy_history.destroy') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger">Delete loghy_history</button>
                            </form>
                        </div>
                    </div>
                    <pre><code class="json">{{ 
                        json_encode(
                            $output,
                            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) 
                    }}</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
