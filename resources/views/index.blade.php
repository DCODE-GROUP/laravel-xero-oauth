<x-app-layout>
    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
        <x-slot name="header">
            <div class="font-semibold text-xl text-gray-800 leading-tight">{{ __('xero.label.header') }}</div>
        </x-slot>

        @if (!$token || $token->toOAuth2Token()->hasExpired())
            <div>
                <p><i>@lang('xero.status.unauthorized')</i></p>
                <a href="{{ route('admin.xero.auth') }}"
                   class="text-blue-400 underline">  @lang('xero.button.authorize') </a>
            </div>
        @else
            <p><i>@lang('xero.status.authorized') </i></p>
        @endif
        <hr class="mb-2"/>
        <h3 class="mt-2">@lang('xero.label.accounts')</h3>
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
                        >@lang('generic.buttons.select')</button>
                    </div>
                </form>
            @endforeach
        </div>
    </div>
</x-app-layout>
