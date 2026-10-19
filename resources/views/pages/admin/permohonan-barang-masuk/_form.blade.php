<form id="permohonanForm" method="POST" action="{{ route('permohonan-barang-masuk.store') }}" enctype="multipart/form-data" novalidate>
    @csrf
    <input type="hidden" name="_method" id="permohonanMethod" value="POST">
    <input type="hidden" name="submit_action" id="submitAction" value="draft">
    <div class="ajax-errors alert alert-danger d-none" role="alert"></div>

    <div class="row">
        <div class="col-12 form-group">
            <label for="tgl_permohonan">Tanggal Permohonan <span class="text-danger">*</span></label>
            <input id="tgl_permohonan" name="tgl_permohonan" type="date" class="form-control" value="{{ now()->format('Y-m-d') }}">
            <small class="text-danger" data-error-for="tgl_permohonan"></small>
        </div>
        <div class="col-md-6 form-group">
            <label for="nama_pic">Nama PIC <span class="text-danger">*</span></label>
            <input id="nama_pic" name="nama_pic" type="text" class="form-control" maxlength="255">
            <small class="text-danger" data-error-for="nama_pic"></small>
        </div>
        <div class="col-md-6 form-group">
            <label for="divisi">Divisi <span class="text-danger">*</span></label>
            <input id="divisi" name="divisi" type="text" class="form-control" maxlength="255">
            <small class="text-danger" data-error-for="divisi"></small>
        </div>
        <div class="col-12 form-group">
            <label for="nm_barang">Nama Barang <span class="text-danger">*</span></label>
            <input id="nm_barang" name="nm_barang" type="text" class="form-control" maxlength="255" placeholder="Contoh: Mesin Gate Barrier">
            <small class="text-danger" data-error-for="nm_barang"></small>
        </div>
        <div class="col-md-6 form-group">
            <label for="lokasi_penyimpanan">Lokasi Penyimpanan <span class="text-danger">*</span></label>
            <input id="lokasi_penyimpanan" name="lokasi_penyimpanan" type="text" class="form-control" maxlength="255">
            <small class="text-danger" data-error-for="lokasi_penyimpanan"></small>
        </div>
        <div class="col-md-6 form-group">
            <label for="kondisi_barang">Kondisi Barang <span class="text-danger">*</span></label>
            <select id="kondisi_barang" name="kondisi_barang" class="form-control">
                <option value="">Pilih kondisi</option>
                <option value="BAIK">BAIK</option>
                <option value="RUSAK">RUSAK</option>
            </select>
            <small class="text-danger" data-error-for="kondisi_barang"></small>
        </div>
        <div class="col-md-6 form-group">
            <label for="qty_barang">Jumlah Barang <span class="text-danger">*</span></label>
            <input id="qty_barang" name="qty_barang" type="number" min="1" step="1" class="form-control">
            <small class="text-danger" data-error-for="qty_barang"></small>
        </div>
        <div class="col-md-6 form-group">
            <label for="asal_barang">Asal Barang <span class="text-danger">*</span></label>
            <input id="asal_barang" name="asal_barang" type="text" class="form-control" maxlength="255">
            <small class="text-danger" data-error-for="asal_barang"></small>
        </div>
        <div class="col-md-6 form-group">
            <label for="bast">BAST <span class="text-danger">*</span></label>
            <input id="bast" name="bast" type="file" accept=".pdf,application/pdf" class="form-control-file">
            <small class="form-text text-muted">PDF, maksimal 10 MB.</small>
            <div id="currentBast" class="small"></div>
            <small class="text-danger" data-error-for="bast"></small>
        </div>
        <div class="col-md-6 form-group">
            <label for="foto">Lampiran Foto <span class="text-danger">*</span></label>
            <input id="foto" name="foto" type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="form-control-file">
            <small class="form-text text-muted">JPG, PNG, GIF, atau WEBP, maksimal 5 MB.</small>
            <div id="currentFoto" class="small"></div>
            <small class="text-danger" data-error-for="foto"></small>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div class="mr-3 mb-2">
            <strong>Validasi Sistem <span class="text-danger">*</span></strong>
            <div class="small text-muted">Jika data atau dokumen wajib belum lengkap, permohonan tetap berstatus Draft dan tidak dapat diajukan untuk approval.</div>
        </div>
        <div class="text-right mb-2">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-outline-dark" id="saveDraft">
                <i class="fas fa-save"></i> Simpan Draft
            </button>
            <button type="submit" class="btn btn-primary" id="submitForApproval">
                <i class="fas fa-paper-plane"></i> Ajukan Approval
            </button>
        </div>
    </div>
</form>
