@extends('layouts.main')
@section('title', 'Tambah Gudang Peralatan')

@section('content')
<section class="section">
    <div class="section-body">
        @include('partials.alert')
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4>Tambah Peralatan Gudang</h4>
                <a href="{{ route('gudang-peralatan.index') }}" class="btn btn-primary">Kembali</a>
            </div>
            <div class="card-body">
                @include('pages.admin.gudang-peralatan._form', ['action' => route('gudang-peralatan.store')])
            </div>
        </div>
    </div>
</section>
@endsection