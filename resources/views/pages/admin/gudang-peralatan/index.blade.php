@extends('layouts.main')
@section('title', 'Gudang Peralatan')

@section('content')
<section class="section custom-section">
    <div class="section-body">
        @include('partials.alert')
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4>Gudang Peralatan</h4>
                <a href="{{ route('gudang-peralatan.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Peralatan</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="table-2">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Foto</th>
                                <th>Nama Barang</th>
                                <th>Qty</th>
                                <th>Lokasi</th>
                                <th>Kondisi</th>
                                <th>Mekanisme Penghapusan</th>  
                                <th>Alasan Penghapusan</th>  
                                <th>Tata Cara </th>  
                                
                                <th>CCTV</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($gudangPeralatans as $data)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        @if ($data->foto)
                                            <img src="{{ asset('storage/' . ltrim($data->foto, '/')) }}" alt="{{ $data->nama_barang }}" style="width: 56px; height: 56px; object-fit: cover;" class="rounded">
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    
                                    <td>{{ $data->nama_barang }}</td>
                                    <td>{{ $data->qty }} {{ $data->satuan }}</td>
                                    <td>{{ $data->gudang_area }}<br><small class="text-muted">{{ $data->lokasi }}</small></td>
                                    <td><span class="badge badge-{{ $data->kondisi === 'BAIK' ? 'success' : 'danger' }}">{{ $data->kondisi }}</span></td>
                                    <td>{{ $data->mekanisme_penghapusan }}</td>
                                    <td>{{ $data->alasan_hapus }}</td>
                                    <td>{{ $data->tata_cara }}</td>
                                    <!-- <td>
                                        @if ($data->latitude !== null && $data->longitude !== null)
                                            <a href="https://www.google.com/maps?q={{ $data->latitude }},{{ $data->longitude }}" target="_blank" rel="noopener">{{ $data->latitude }}, {{ $data->longitude }}</a>
                                        @else
                                            <span class="text-muted">Belum diatur</span>
                                        @endif
                                    </td> -->
                                    <td>
                                        @if ($data->url_cctv)
                                            <button type="button" class="btn btn-info btn-sm view-cctv" data-url="{{ $data->url_cctv }}" data-proxy-url="{{ route('gudang-peralatan.cctv', $data) }}"><i class="fas fa-video"></i> Lihat</button>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex">
                                            <a href="{{ route('gudang-peralatan.edit', $data) }}" class="btn btn-success btn-sm"><i class="fas fa-edit"></i></a>
                                            <form method="POST" action="{{ route('gudang-peralatan.destroy', $data) }}" class="ml-1 delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash-alt"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted">Belum ada data peralatan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="cctvModal" tabindex="-1" role="dialog" aria-labelledby="cctvModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cctvModalLabel">Live CCTV Gudang</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body p-0" style="background: #111;">
                <div id="cctv-image-wrapper" class="text-center p-2" style="display: none; min-height: 240px;">
                    <img id="cctv-image" src="" alt="Live CCTV Gudang" style="max-width: 100%; max-height: 70vh; object-fit: contain;">
                </div>
                <div id="cctv-frame-wrapper" class="embed-responsive embed-responsive-16by9">
                    <iframe id="cctv-frame" class="embed-responsive-item" src="" title="CCTV Gudang" allow="autoplay; fullscreen" allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    $(function () {
        let cctvRefreshTimer = null;

        $('.view-cctv').on('click', function () {
            const url = $(this).data('url');
            const proxyUrl = $(this).data('proxy-url');
            const isSnapshot = /\/picture(?:[/?]|$)/i.test(url);

            if (cctvRefreshTimer) {
                clearInterval(cctvRefreshTimer);
                cctvRefreshTimer = null;
            }

            $('#cctv-image-wrapper').toggle(isSnapshot);
            $('#cctv-frame-wrapper').toggle(!isSnapshot);

            if (isSnapshot) {
                const refreshSnapshot = function () {
                    $('#cctv-image').attr('src', proxyUrl + '?t=' + Date.now());
                };

                refreshSnapshot();
                cctvRefreshTimer = setInterval(refreshSnapshot, 3000);
            } else {
                $('#cctv-frame').attr('src', url);
            }

            $('#cctvModal').modal('show');
        });

        $('#cctvModal').on('hidden.bs.modal', function () {
            if (cctvRefreshTimer) {
                clearInterval(cctvRefreshTimer);
                cctvRefreshTimer = null;
            }

            $('#cctv-frame').attr('src', '');
            $('#cctv-image').attr('src', '');
        });

        $('.delete-form').on('submit', function (event) {
            if (!window.confirm('Hapus data peralatan ini?')) {
                event.preventDefault();
            }
        });
    });
</script>
@endpush