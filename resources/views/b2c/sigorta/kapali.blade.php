@extends('b2c.layouts.app')

@section('title', 'Seyahat Sigortası')

@section('content')
<div class="container py-5 text-center" style="max-width:500px">
    <i class="fas fa-shield-alt fa-4x text-muted mb-4"></i>
    <h3 class="fw-bold">Seyahat Sigortası</h3>
    <p class="text-muted">
        Seyahat sigortası hizmeti çok yakında bu sayfada aktif olacak.
        Şimdilik <a href="{{ route('b2c.home') }}">ana sayfaya dönebilirsiniz</a>.
    </p>
</div>
@endsection
