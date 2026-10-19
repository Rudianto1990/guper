@extends('layouts.main')
@section('title', 'Live CCTV')

@section('content')
<section class="section custom-section">
    <div class="section-body">
        <div id="cctvMessage"></div>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4>Data Live CCTV</h4>
                <button type="button" class="btn btn-primary" id="createCctv">
                    <i class="fas fa-plus"></i> Tambah Kamera
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="cctvTable">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Nama Kamera</th>
                                <th>Kode Inventaris</th>
                                <th>Lokasi</th>
                                <th>IP Address</th>
                                <th>Jenis / Merk</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($cctvs as $cctv)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $cctv->nama_camera }}</td>
                                    <td>{{ $cctv->inventory_code ?? '-' }}</td>
                                    <td>{{ $cctv->location }}</td>
                                    <td>{{ $cctv->ip_address }}</td>
                                    <td>{{ $cctv->type_camera }} / {{ $cctv->merk ?? '-' }}</td>
                                    <td>{{ $cctv->source_status ?? '-' }}</td>
                                    <td>
                                        <button type="button" class="btn btn-info btn-sm view-cctv" data-id="{{ $cctv->id }}"><i class="fas fa-play"></i> Live</button>
                                        <button type="button" class="btn btn-success btn-sm edit-cctv" data-id="{{ $cctv->id }}"><i class="fas fa-edit"></i></button>
                                        <button type="button" class="btn btn-danger btn-sm delete-cctv" data-id="{{ $cctv->id }}"><i class="fas fa-trash-alt"></i></button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted">Belum ada data CCTV.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="cctvFormModal" tabindex="-1" role="dialog" aria-labelledby="cctvFormTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <form id="cctvForm" method="POST" action="{{ route('cctv.store') }}">
                @csrf
                <input type="hidden" name="_method" id="cctvMethod" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="cctvFormTitle">Tambah Kamera CCTV</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div id="cctvFormErrors" class="alert alert-danger d-none"></div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h6 class="mb-1">Pin Lokasi Kamera</h6>
                                <small class="text-muted">Klik peta atau geser pin untuk mengisi koordinat. Bisa juga gunakan lokasi perangkat.</small>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="useCctvLocation">
                                <i class="fas fa-location-arrow"></i> Gunakan Geolocation
                            </button>
                        </div>
                        <div id="cctvLocationMap"></div>
                    </div>
                    <div class="form-row">
                        @php
                            $fields = [
                                ['source_id', 'Source ID', 'number'],
                                ['inventory_code', 'Kode Inventaris', 'text'],
                                ['nama_camera', 'Nama Kamera', 'text', true],
                                ['location', 'Lokasi', 'text', true],
                                ['ip_address', 'IP Address', 'text', true],
                                ['type_camera', 'Jenis Kamera', 'text', true],
                                ['rtsp_url', 'RTSP / Stream URL', 'text', true],
                                ['latitude', 'Latitude', 'text', true],
                                ['longitude', 'Longitude', 'text', true],
                                ['source_name', 'Source Name', 'text'],
                                ['sub_division', 'Sub Division', 'text'],
                                ['category', 'Category', 'text'],
                                ['jenis_kategori', 'Jenis Kategori', 'text'],
                                ['merk', 'Merk', 'text'],
                                ['serial_number', 'Serial Number', 'text'],
                                ['source_status', 'Source Status', 'text'],
                                ['reg_date', 'Tanggal Registrasi', 'date'],
                                ['nvr', 'NVR', 'text'],
                                ['nomor_urut', 'Nomor Urut', 'text'],
                                ['link_img', 'Link Gambar', 'text'],
                                ['source_model', 'Source Model', 'text'],
                                ['source_created_at', 'Source Created At', 'datetime-local'],
                                ['source_updated_at', 'Source Updated At', 'datetime-local'],
                            ];
                        @endphp
                        @foreach ($fields as $field)
                            <div class="form-group col-md-6">
                                <label for="cctv_{{ $field[0] }}">{{ $field[1] }} @if (!empty($field[3]))<span class="text-danger">*</span>@endif</label>
                                <input
                                    type="{{ $field[2] }}"
                                    class="form-control cctv-input"
                                    id="cctv_{{ $field[0] }}"
                                    name="{{ $field[0] }}"
                                    @if (!empty($field[3])) required @endif
                                    @if ($field[0] === 'source_id') min="0" @endif
                                    maxlength="{{ in_array($field[0], ['nama_camera', 'location']) ? 100 : (in_array($field[0], ['ip_address', 'type_camera', 'source_status', 'nvr', 'nomor_urut']) ? 50 : 255) }}"
                                >
                                <small class="text-danger field-error" data-field="{{ $field[0] }}"></small>
                            </div>
                        @endforeach
                    </div>
                    <small class="text-muted">Preview live pada browser membutuhkan URL HTTP/HLS yang dapat diputar. RTSP native memerlukan server gateway RTSP ke HLS/WebRTC.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="saveCctv"><i class="fas fa-save"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="cctvLiveModal" tabindex="-1" role="dialog" aria-labelledby="cctvLiveTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cctvLiveTitle">Live CCTV</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body p-0 bg-dark">
                <div id="cctvLiveMessage" class="alert alert-warning m-3 d-none"></div>
                <video id="cctvLiveVideo" class="w-100 d-none" controls autoplay playsinline></video>
                <iframe id="cctvLiveFrame" class="w-100 d-none" style="height:70vh;border:0" title="Live CCTV" allow="autoplay; fullscreen" allowfullscreen></iframe>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
    #cctvLocationMap {
        position: relative;
        z-index: 1;
        width: 100%;
        height: 360px;
        overflow: hidden;
        border-radius: 4px;
    }

    #cctvLocationMap .leaflet-container {
        width: 100%;
        height: 100%;
        background: #d8e7ed;
    }

    #cctvLocationMap .leaflet-pane,
    #cctvLocationMap .leaflet-top,
    #cctvLocationMap .leaflet-bottom {
        position: absolute;
    }

    #cctvLocationMap .leaflet-tile,
    #cctvLocationMap .leaflet-marker-icon,
    #cctvLocationMap .leaflet-marker-shadow {
        max-width: none !important;
        max-height: none !important;
    }
</style>
@endpush

@push('script')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.17/dist/hls.min.js"></script>
<script>
    $(function () {
        const baseUrl = @json(url('/cctv'));
        const dataUrl = @json(route('cctv.data'));
        const $form = $('#cctvForm');
        let locationMap = null;
        let locationMarker = null;
        let mapInitialized = false;
        let hlsPlayer = null;
        const defaultMapPosition = [-6.2, 106.8166667];
        const fieldNames = [
            'source_id', 'inventory_code', 'nama_camera', 'location', 'ip_address', 'type_camera', 'rtsp_url',
            'latitude', 'longitude', 'source_name', 'sub_division', 'category', 'jenis_kategori', 'merk',
            'serial_number', 'source_status', 'reg_date', 'nvr', 'nomor_urut', 'link_img', 'source_model',
            'source_created_at', 'source_updated_at'
        ];

        function alertMessage(text, type) {
            $('#cctvMessage').html($('<div>').addClass('alert alert-' + type + ' alert-dismissible fade show')
                .append(document.createTextNode(text))
                .append('<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>'));
        }

        function renderRows(items) {
            const $body = $('#cctvTable tbody').empty();
            if (!items.length) {
                $('<tr>').append($('<td>').attr('colspan', 8).addClass('text-center text-muted').text('Belum ada data CCTV.')).appendTo($body);
                return;
            }

            $.each(items, function (index, camera) {
                const $actions = $('<td>');
                $('<button>').attr({ type: 'button', 'data-id': camera.id }).addClass('btn btn-info btn-sm view-cctv')
                    .append($('<i>').addClass('fas fa-play')).append(' Live').appendTo($actions);
                $('<button>').attr({ type: 'button', 'data-id': camera.id }).addClass('btn btn-success btn-sm ml-1 edit-cctv')
                    .append($('<i>').addClass('fas fa-edit')).appendTo($actions);
                $('<button>').attr({ type: 'button', 'data-id': camera.id }).addClass('btn btn-danger btn-sm ml-1 delete-cctv')
                    .append($('<i>').addClass('fas fa-trash-alt')).appendTo($actions);
                $('<tr>').append($('<td>').text(index + 1))
                    .append($('<td>').text(camera.nama_camera))
                    .append($('<td>').text(camera.inventory_code || '-'))
                    .append($('<td>').text(camera.location))
                    .append($('<td>').text(camera.ip_address))
                    .append($('<td>').text(camera.type_camera + ' / ' + (camera.merk || '-')))
                    .append($('<td>').text(camera.source_status || '-'))
                    .append($actions).appendTo($body);
            });
        }

        function refreshRows() {
            return $.getJSON(dataUrl).done(function (response) {
                renderRows(response.data);
            }).fail(function () {
                alertMessage('Daftar kamera CCTV gagal dimuat.', 'danger');
            });
        }

        function clearErrors() {
            $('#cctvFormErrors').addClass('d-none').empty();
            $('.field-error').empty();
        }

        function setFormValues(camera) {
            $.each(fieldNames, function (_, field) {
                let value = camera[field] || '';
                if (value && field.indexOf('_at') !== -1) value = value.substring(0, 16);
                $('#cctv_' + field).val(value);
            });
        }

        function placeMapMarker(position) {
            if (locationMarker) {
                locationMarker.setLatLng(position);
            } else {
                locationMarker = L.marker(position, { draggable: true }).addTo(locationMap);
                locationMarker.on('dragend', function (event) {
                    const markerPosition = event.target.getLatLng();
                    $('#cctv_latitude').val(markerPosition.lat.toFixed(7));
                    $('#cctv_longitude').val(markerPosition.lng.toFixed(7));
                });
            }
        }

        function setMapPosition(latitude, longitude, zoom) {
            const lat = Number(latitude);
            const lng = Number(longitude);
            if (!Number.isFinite(lat) || !Number.isFinite(lng) || lat < -90 || lat > 90 || lng < -180 || lng > 180) {
                return;
            }

            const position = [lat, lng];
            $('#cctv_latitude').val(lat.toFixed(7));
            $('#cctv_longitude').val(lng.toFixed(7));
            placeMapMarker(position);
            locationMap.setView(position, zoom || Math.max(locationMap.getZoom(), 15));
        }

        function resetMapPosition() {
            if (!locationMap) return;

            $('#cctv_latitude, #cctv_longitude').val('');
            placeMapMarker(defaultMapPosition);
            locationMap.setView(defaultMapPosition, 5);
        }

        function stopLivePlayback() {
            if (hlsPlayer) {
                hlsPlayer.destroy();
                hlsPlayer = null;
            }

            const video = $('#cctvLiveVideo').get(0);
            video.pause();
            video.removeAttribute('src');
            video.load();
            $('#cctvLiveVideo, #cctvLiveFrame').addClass('d-none');
            $('#cctvLiveFrame').attr('src', '');
        }

        function showLiveError(message) {
            $('#cctvLiveMessage').removeClass('d-none').text(message);
        }

        function initializeMap() {
            if (!mapInitialized) {
                locationMap = L.map('cctvLocationMap').setView(defaultMapPosition, 5);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(locationMap);
                locationMap.on('click', function (event) {
                    setMapPosition(event.latlng.lat, event.latlng.lng, 16);
                });
                mapInitialized = true;
            }

            window.setTimeout(function () {
                locationMap.invalidateSize();
                const latitude = $('#cctv_latitude').val();
                const longitude = $('#cctv_longitude').val();
                if (latitude && longitude) {
                    setMapPosition(latitude, longitude);
                } else {
                    resetMapPosition();
                }
            }, 150);
        }

        $('#cctvFormModal').on('shown.bs.modal', initializeMap);

        $('#cctv_latitude, #cctv_longitude').on('change', function () {
            setMapPosition($('#cctv_latitude').val(), $('#cctv_longitude').val());
        });

        $('#useCctvLocation').on('click', function () {
            if (!navigator.geolocation) {
                window.alert('Browser ini tidak mendukung geolocation.');
                return;
            }

            navigator.geolocation.getCurrentPosition(function (position) {
                setMapPosition(position.coords.latitude, position.coords.longitude, 16);
            }, function () {
                window.alert('Lokasi tidak dapat diperoleh. Pastikan izin lokasi sudah diberikan.');
            }, { enableHighAccuracy: true, timeout: 10000 });
        });

        $('#createCctv').on('click', function () {
            $form[0].reset();
            $form.attr('action', baseUrl);
            $('#cctvMethod').val('POST');
            $('#cctvFormTitle').text('Tambah Kamera CCTV');
            clearErrors();
            resetMapPosition();
            $('#cctvFormModal').modal('show');
        });

        $form.on('submit', function (event) {
            event.preventDefault();
            clearErrors();
            const $button = $('#saveCctv').prop('disabled', true);
            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: $form.serialize(),
                headers: { 'Accept': 'application/json' }
            }).done(function (response) {
                $('#cctvFormModal').modal('hide');
                alertMessage(response.message, 'success');
                refreshRows();
            }).fail(function (xhr) {
                const errors = xhr.responseJSON && xhr.responseJSON.errors;
                if (errors) {
                    $.each(errors, function (field, messages) {
                        $('.field-error[data-field="' + field + '"]').text(messages[0]);
                    });
                    $('#cctvFormErrors').removeClass('d-none').text('Periksa kembali data yang wajib diisi atau formatnya.');
                } else {
                    const errorMessage = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message : 'Data CCTV gagal disimpan.';
                    $('#cctvFormErrors').removeClass('d-none').text(errorMessage);
                }
            }).always(function () {
                $button.prop('disabled', false);
            });
        });

        $('#cctvTable').on('click', '.edit-cctv', function () {
            $.getJSON(baseUrl + '/' + $(this).data('id')).done(function (response) {
                $form[0].reset();
                setFormValues(response.data);
                $form.attr('action', baseUrl + '/' + response.data.id);
                $('#cctvMethod').val('PUT');
                $('#cctvFormTitle').text('Edit Kamera CCTV');
                clearErrors();
                $('#cctvFormModal').modal('show');
            }).fail(function () {
                alertMessage('Data kamera CCTV gagal dimuat.', 'danger');
            });
        });

        $('#cctvTable').on('click', '.delete-cctv', function () {
            const id = $(this).data('id');
            if (!window.confirm('Hapus data kamera CCTV ini?')) return;
            $.ajax({
                url: baseUrl + '/' + id,
                method: 'POST',
                data: { _token: $form.find('[name="_token"]').val(), _method: 'DELETE' },
                headers: { 'Accept': 'application/json' }
            }).done(function (response) {
                alertMessage(response.message, 'success');
                refreshRows();
            }).fail(function (xhr) {
                alertMessage(xhr.responseJSON?.message || 'Data CCTV gagal dihapus.', 'danger');
            });
        });

        $('#cctvTable').on('click', '.view-cctv', function () {
            const id = $(this).data('id');
            const cameraName = $(this).closest('tr').find('td').eq(1).text();
            $('#cctvLiveTitle').text('Live CCTV - ' + cameraName);
            stopLivePlayback();
            $('#cctvLiveMessage').removeClass('d-none').text('Menyiapkan stream kamera melalui gateway...');
            $('#cctvLiveModal').modal('show');

            $.getJSON(baseUrl + '/' + id + '/playback').done(function (response) {
                const playback = response.data;
                const video = $('#cctvLiveVideo').get(0);
                $('#cctvLiveMessage').addClass('d-none').empty();

                if (playback.type === 'hls' && video.canPlayType('application/vnd.apple.mpegurl')) {
                    $('#cctvLiveVideo').attr('src', playback.url).removeClass('d-none');
                    video.load();
                } else if (playback.type === 'hls' && window.Hls && Hls.isSupported()) {
                    hlsPlayer = new Hls();
                    hlsPlayer.loadSource(playback.url);
                    hlsPlayer.attachMedia(video);
                    $('#cctvLiveVideo').removeClass('d-none');
                    hlsPlayer.on(Hls.Events.ERROR, function (_event, data) {
                        if (data.fatal) {
                            showLiveError('Stream HLS gagal diputar. Pastikan kamera dapat dijangkau MediaMTX dan codec stream didukung browser.');
                        }
                    });
                } else if (playback.type === 'iframe') {
                    $('#cctvLiveFrame').attr('src', playback.url).removeClass('d-none');
                } else {
                    showLiveError('Browser ini tidak mendukung pemutaran HLS. Coba gunakan browser yang mendukung HLS atau aktifkan HLS.js.');
                }
            }).fail(function (xhr) {
                const message = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message : 'Stream kamera gagal disiapkan.';
                showLiveError(message);
            });
        });

        $('#cctvLiveModal').on('hidden.bs.modal', function () {
            stopLivePlayback();
        });

        refreshRows();
    });
</script>
@endpush
