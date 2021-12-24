@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">

            <div class="card">
                <div class="card-header">{{ __('Profile') }}</div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2">
                            avatar
                        </div>
                        <div class="col-md-10">
                            <pre><code class="json">{{ 
                                json_encode(
                                    $user,
                                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) 
                            }}</code></pre>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">{{ __('SNS') }}</div>
                <div class="card-body">
                    <table class="table col-8 offset-2">
                        <tbody>
                            @foreach ($snsList as $sns)
                                <tr class="align-middle">
                                    <td class="align-middle">{{ $sns->name }}</td>
                                    @if ($sns->isLinked)
                                        <td class="align-middle small text-success">連携済</td>
                                        <td class="align-middle text-right">
                                            <form method="post" action="{{ route('social_identities.destroy', $sns->id) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-secondary">解除</button>
                                            </form>
                                        </td>
                                    @else
                                        <td class="align-middle small">未連携</td>
                                        <td class="align-middle text-right">
                                            <button class="btn btn-primary"
                                                onclick="location.href='{{ $sns->loginUrl }}'"
                                            >連携</button>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
