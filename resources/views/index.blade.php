@extends(config('laravel-xero-oauth.app_layout'))

@section('content')
    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
        <div class="font-semibold text-xl text-gray-800 leading-tight">@lang('xero-oauth-translations::xero.label.header')</div>

        @if (!$token || $token->toOAuth2Token()->hasExpired())
            <div>
                <p><i>@lang('xero-oauth-translations::xero.status.unauthorized')</i></p>
                <a href="{{ route('xero.auth') }}"
                   class="text-blue-400 underline">@lang('xero-oauth-translations::xero.button.authorize')</a>
            </div>
        @else
            <p><i>@lang('xero-oauth-translations::xero.status.authorized') </i></p>
        @endif
        <hr class="mb-2"/>
        <h3 class="mt-2">@lang('xero-oauth-translations::xero.label.accounts')</h3>
        <div class="mt-2 flex">
            @foreach($tenants as $index => $tenant)
                <form action="{{ route('xero.tenant.update', $tenant->tenantId) }}" method="POST"
                      novalidate class="flex">
                    @csrf
                    <div class="bg-white p-10 rounded-lg shadow-md text-center
                    @if($index !== 0)
                            ml-2
                    @endif
                            ">
                        <h1 class="text-xl font-bold">{{$tenant->tenantType}}</h1>
                        <div class="mt-4 mb-2">
                            <p class="text-gray-600">{{$tenant->tenantName}}</p>
                        </div>
                        <button
                                class="text-white py-3 px-8 mt-4 rounded text-sm font-semibold hover:bg-opacity-75
                            @if($tenant->tenantId === $currentTenantId) bg-gray-400 @else bg-blue-400 @endif"
                                @if($tenant->tenantId === $currentTenantId) disabled @endif
                        >@lang('xero-oauth-translations::xero.button.select')</button>
                    </div>
                </form>
            @endforeach
        </div>
    </div>
@endsection
