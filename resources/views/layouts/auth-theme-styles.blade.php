@auth
    @php $role = auth()->user()->role ?? 'acente'; @endphp
    @if(in_array($role, ['admin', 'superadmin'], true))
        @include('admin.partials.theme-styles')
    @else
        @include('acente.partials.theme-styles')
    @endif
@endauth
