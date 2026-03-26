@auth
    @php($role = auth()->user()->role ?? 'acente')
    @if(in_array($role, ['admin', 'superadmin'], true))
        @include('admin.partials.theme-script')
    @else
        @include('acente.partials.theme-script')
    @endif
@endauth
