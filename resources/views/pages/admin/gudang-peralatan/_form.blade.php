@php
    $gudangPeralatan = $gudangPeralatan ?? null;
    $editing = isset($gudangPeralatan);
    $latitude = old('latitude', $gudangPeralatan?->latitude ?? -6.1072377);
    $longitude = old('longitude', $gudangPeralatan?->longitude ?? 106.8839000);
@endphp

@push('style')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
    #warehouse-map {
        position: relative;
        width: 100%;
        height: 360px;
        overflow: hidden;
        border-radius: 4px;
        z-index: 1;
    }

    #warehouse-map .leaflet-container {
        width: 100%;
        height: 100%;
        overflow: hidden;
        background: #d8e7ed;
    }

    #warehouse-map .leaflet-pane,
    #warehouse-map .leaflet-top,
    #warehouse-map .leaflet-bottom {
        position: absolute;
    }

    #warehouse-map .leaflet-tile,
    #warehouse-map .leaflet-marker-icon,
    #warehouse-map .leaflet-marker-shadow {
        max-width: none !important;
        max-height: none !important;
    }
</style>
@endpush

<form method="POST" action="{{ $action }}" enctype="multipart/form-data">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <div class="row">
        @if ($editing)
            <div class="col-md-6">
                <div class="form-group">
                    <label for="id_barang">ID Barang</label>
                    <input id="id_barang" type="text" class="form-control" value="{{ $gudangPeralatan->id_barang }}" readonly>
                </div>
            </div>
        @endif
        <div class="col-md-6">
            <div class="form-group">
                <label for="nama_barang">Nama Barang <span class="text-danger">*</span></label>
                <input id="nama_barang" name="nama_barang" type="text" class="form-control @error('nama_barang') is-invalid @enderror" value="{{ old('nama_barang', $gudangPeralatan?->nama_barang) }}" required>
                @error('nama_barang')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="qty">Jumlah <span class="text-danger">*</span></label>
                <input id="qty" name="qty" type="number" min="0" class="form-control @error('qty') is-invalid @enderror" value="{{ old('qty', $gudangPeralatan?->qty ?? 0) }}" required>
                @error('qty')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="tahun">Tahun</label>
                <input id="tahun" name="tahun" type="number" min="1900" max="{{ date('Y') + 1 }}" class="form-control @error('tahun') is-invalid @enderror" value="{{ old('tahun', $gudangPeralatan?->tahun) }}">
                @error('tahun')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="satuan">Satuan <span class="text-danger">*</span></label>
                <input id="satuan" name="satuan" type="text" class="form-control @error('satuan') is-invalid @enderror" value="{{ old('satuan', $gudangPeralatan?->satuan) }}" placeholder="Unit" required>
                @error('satuan')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="gudang_area">Area Gudang <span class="text-danger">*</span></label>
                <input id="gudang_area" name="gudang_area" type="text" class="form-control @error('gudang_area') is-invalid @enderror" value="{{ old('gudang_area', $gudangPeralatan?->gudang_area) }}" required>
                @error('gudang_area')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="lokasi">Lokasi Detail <span class="text-danger">*</span></label>
                <input id="lokasi" name="lokasi" type="text" class="form-control @error('lokasi') is-invalid @enderror" value="{{ old('lokasi', $gudangPeralatan?->lokasi) }}" required>
                @error('lokasi')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="kondisi">Kondisi <span class="text-danger">*</span></label>
                <select id="kondisi" name="kondisi" class="form-control @error('kondisi') is-invalid @enderror" required>
                    @foreach (['BAIK', 'RUSAK'] as $condition)
                        <option value="{{ $condition }}" @selected(old('kondisi', $gudangPeralatan?->kondisi ?? 'BAIK') === $condition)>{{ $condition }}</option>
                    @endforeach
                </select>
                @error('kondisi')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="foto">Foto Peralatan</label>
                <input id="foto" name="foto" type="file" accept="image/jpeg,image/png,image/webp" class="form-control-file @error('foto') is-invalid @enderror">
                <small class="form-text text-muted">Format JPG, PNG, atau WEBP. Maksimal 5 MB.</small>
                @error('foto')<div class="text-danger small">{{ $message }}</div>@enderror
                @if ($gudangPeralatan?->foto)
                    <img src="{{ asset('storage/' . ltrim($gudangPeralatan->foto, '/')) }}" alt="Foto {{ $gudangPeralatan->nama_barang }}" class="img-thumbnail mt-2" style="max-width: 140px; max-height: 100px; object-fit: cover;">
                @endif
            </div>
        </div>
    </div>


    <hr>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="mekanisme_penghapusan">Mekanisme Penghapusan</label>
                <textarea id="mekanisme_penghapusan" name="mekanisme_penghapusan" rows="4" class="form-control @error('mekanisme_penghapusan') is-invalid @enderror">{{ old('mekanisme_penghapusan', $gudangPeralatan?->mekanisme_penghapusan) }}</textarea>
                @error('mekanisme_penghapusan')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="alasan_hapus">Alasan Hapus</label>
                <textarea id="alasan_hapus" name="alasan_hapus" rows="4" class="form-control @error('alasan_hapus') is-invalid @enderror">{{ old('alasan_hapus', $gudangPeralatan?->alasan_hapus) }}</textarea>
                @error('alasan_hapus')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="tata_cara">Tata Cara</label>
                <textarea id="tata_cara" name="tata_cara" rows="4" class="form-control @error('tata_cara') is-invalid @enderror">{{ old('tata_cara', $gudangPeralatan?->tata_cara) }}</textarea>
                @error('tata_cara')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <!-- <div class="col-md-6">
            <div class="form-group">
                <label for="url_cctv">URL CCTV</label>
                <input id="url_cctv" name="url_cctv" type="url" class="form-control @error('url_cctv') is-invalid @enderror" value="{{ old('url_cctv', $gudangPeralatan?->url_cctv) }}" placeholder="https://...">
                <small class="form-text text-muted">Gunakan URL kamera yang dapat ditampilkan di iframe.</small>
                @error('url_cctv')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div> -->
    </div>

    <hr>
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h5 class="mb-1">Lokasi Gudang</h5>
            <small class="text-muted">Klik peta atau gunakan lokasi perangkat untuk mengisi koordinat.</small>
        </div>
        <button type="button" id="use-current-location" class="btn btn-outline-primary btn-sm"><i class="fas fa-location-arrow"></i> Gunakan Lokasi Saya</button>
    </div>
    <div id="warehouse-map" class="mb-3"></div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="latitude">Latitude</label>
                <input id="latitude" name="latitude" type="number" step="any" class="form-control @error('latitude') is-invalid @enderror" value="{{ $latitude }}" placeholder="-6.2000000">
                @error('latitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="longitude">Longitude</label>
                <input id="longitude" name="longitude" type="number" step="any" class="form-control @error('longitude') is-invalid @enderror" value="{{ $longitude }}" placeholder="106.8166667">
                @error('longitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    <div class="form-group mb-0">
        <a href="{{ route('gudang-peralatan.index') }}" class="btn btn-light">Batal</a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
    </div>
</form>

@push('script')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const latitudeInput = document.getElementById('latitude');
        const longitudeInput = document.getElementById('longitude');
        const initialLatitude = parseFloat(latitudeInput.value);
        const initialLongitude = parseFloat(longitudeInput.value);
        const hasInitialPosition = Number.isFinite(initialLatitude) && Number.isFinite(initialLongitude);
        const defaultPosition = hasInitialPosition ? [initialLatitude, initialLongitude] : [-6.1072377, 106.8839];
        const defaultZoom = hasInitialPosition ? 16 : 15;
        const map = L.map('warehouse-map').setView(defaultPosition, defaultZoom);
        let marker = hasInitialPosition ? L.marker(defaultPosition).addTo(map) : null;

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        function setPosition(latitude, longitude) {
            latitudeInput.value = latitude.toFixed(7);
            longitudeInput.value = longitude.toFixed(7);
            const position = [latitude, longitude];

            if (marker) {
                marker.setLatLng(position);
            } else {
                marker = L.marker(position).addTo(map);
            }

            map.setView(position, 16);
        }

        map.on('click', function (event) {
            setPosition(event.latlng.lat, event.latlng.lng);
        });

        document.getElementById('use-current-location').addEventListener('click', function () {
            if (!navigator.geolocation) {
                window.alert('Browser ini tidak mendukung geolocation.');
                return;
            }

            navigator.geolocation.getCurrentPosition(
                function (position) {
                    setPosition(position.coords.latitude, position.coords.longitude);
                },
                function () {
                    window.alert('Lokasi tidak dapat diperoleh. Pastikan izin lokasi sudah diberikan.');
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        });
    });
</script>
@endpush