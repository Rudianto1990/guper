@extends('layouts.main')
@section('title', 'Permohonan Barang Keluar')

@section('content')
<section class="section custom-section">
    <div class="section-body">
        <div id="outMessage"></div>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4>Form Permohonan Pengeluaran Barang</h4>
                @if (auth()->user()->roles === 'admin')
                    <button type="button" class="btn btn-primary" id="createOutRequest">
                        <i class="fas fa-plus"></i> Tambah Pengeluaran
                    </button>
                @endif
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="outTable">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Kode Barang Out</th>
                                <th>Barang / Kode</th>
                                <th>Divisi / PIC</th>
                                <th>Jumlah / Tujuan</th>
                                <th>Tanggal Keluar</th>
                                <th>Status / Riwayat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($permohonanBarangOuts as $item)
                                <tr data-id="{{ $item->id }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $item->kd_barang_out }}</td>
                                    <td>{{ $item->nm_barang }}<br><small class="text-muted">{{ $item->kd_barang }}</small></td>
                                    <td>{{ $item->divisi }}<br><small class="text-muted">{{ $item->nama_pic }}</small></td>
                                    <td>{{ $item->qty_barang }} unit<br><small>{{ $item->tujuan_barang }}</small></td>
                                    <td>{{ $item->tgl_permohonan_out?->format('d-m-Y') ?? '-' }}<br><small class="text-muted"><i class="far fa-clock"></i> Dibuat {{ $item->created_at->locale('id')->diffForHumans() }}</small></td>
                                    <td>
                                        {{ $item->status_approved }}
                                        @foreach ($item->approvalLogs as $log)
                                            <div class="small text-muted"><i class="far fa-clock"></i> {{ $log->action }} {{ $log->created_at->locale('id')->diffForHumans() }}</div>
                                        @endforeach
                                    </td>
                                    <td><button type="button" class="btn btn-outline-primary btn-sm inspect-out" data-id="{{ $item->id }}"><i class="far fa-eye"></i> Periksa</button></td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted">Belum ada permohonan pengeluaran barang.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="outFormModal" tabindex="-1" role="dialog" aria-labelledby="outFormModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="outForm" method="POST" action="{{ route('permohonan-barang-out.store') }}" enctype="multipart/form-data" novalidate>
                @csrf
                <input type="hidden" name="_method" id="outMethod" value="POST">
                <input type="hidden" name="submit_action" id="outSubmitAction" value="draft">
                <div class="modal-header">
                    <h5 class="modal-title" id="outFormModalLabel">Tambah Pengeluaran Barang</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
                </div>
             <div class="modal-body">
                <div id="outFormError" class="alert alert-danger d-none"></div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="kd_barang">Kode Barang <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="kd_barang" id="kd_barang" autocomplete="off" required>
                        <small class="text-muted">Masukkan kode barang yang sudah disetujui admin dan supervisor.</small>
                        <small class="text-danger d-block" id="kdBarangError"></small>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="kd_barang_out">Kode Barang Out</label>
                        <input type="text" class="form-control" id="kd_barang_out" disabled>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="nm_barang">Nama Barang</label>
                        <input type="text" class="form-control" id="nm_barang" disabled>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="divisi">Divisi</label>
                        <input type="text" class="form-control" id="divisi" disabled>
                    </div>

                    <!-- PIC NAMA & STOK BARANG DIGABUNG DI KOLOM INI -->
                    <div class="form-group col-md-6">
                        <label for="nama_pic">PIC Nama</label>
                        <input type="text" class="form-control" id="nama_pic" disabled>
                        
                        <!-- Posisi Stok Barang sekarang ada di bawah PIC Nama -->
                        <div class="form-control-plaintext font-weight-bold mt-2" id="stockLabel">Stok Barang: -</div>
                        <small class="text-danger" id="stockError"></small>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="tgl_permohonan_out">Tanggal Keluar Barang <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="tgl_permohonan_out" id="tgl_permohonan_out" required>
                        <small class="text-danger" id="outDateError"></small>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="qty_barang">Jumlah Barang Keluar <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="qty_barang" id="qty_barang" min="1" step="1" required>
                        <small class="text-danger" id="qtyError"></small>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="tujuan_barang">Tujuan Barang <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="tujuan_barang" id="tujuan_barang" maxlength="255" required>
                        <small class="text-danger" id="tujuanError"></small>
                    </div>
                    <div class="form-group col-12">
                        <label for="foto">Lampiran Foto</label>
                        <input type="file" class="form-control" name="foto" id="foto" accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                        <small class="form-text text-muted">Format JPG, JPEG, PNG; maksimal 5 MB. Wajib saat mengajukan approval.</small>
                        <div id="currentPhoto" class="mt-2"></div>
                        <small class="text-danger" id="fotoError"></small>
                    </div>
                </div>
            </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <div id="editButtons">
                        <button type="button" class="btn btn-outline-secondary save-out" data-action="draft"><i class="fas fa-save"></i> Simpan Draft</button>
                        <button type="button" class="btn btn-primary save-out" data-action="submit"><i class="fas fa-paper-plane"></i> Ajukan Approval</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    $(function () {
        const baseUrl = @json(url('/permohonan-barang-out'));
        const dataUrl = @json(route('permohonan-barang-out.data'));
        const lookupUrl = @json(url('/permohonan-barang-out/lookup'));
        const $form = $('#outForm');
        let activeSource = null;
        let activeRequest = null;

        function showMessage(text, type) {
            $('#outMessage').html($('<div>').addClass('alert alert-' + type + ' alert-dismissible fade show')
                .append(document.createTextNode(text))
                .append('<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>'));
        }

        function clearErrors() {
            $('#outFormError, #kdBarangError, #outDateError, #stockError, #qtyError, #tujuanError, #fotoError')
                .empty().addClass('d-none');
        }

        function setSource(item) {
            activeSource = item;
            $('#kd_barang').val(item.kd_barang);
            $('#nm_barang').val(item.nm_barang);
            $('#divisi').val(item.divisi);
            $('#nama_pic').val(item.nama_pic);
            $('#stockLabel').text('Stok Barang: ' + item.stock_tersedia);
            $('#qty_barang').attr('max', item.stock_tersedia);
            $('#stockError').toggleClass('d-none', item.stock_tersedia > 0)
                .text(item.stock_tersedia > 0 ? '' : 'Stock Barang Tidak Sesuai/Habis!!!');
        }

        function clearSource() {
            activeSource = null;
            $('#nm_barang, #divisi, #nama_pic').val('');
            $('#stockLabel').text('Stok Barang: -');
            $('#qty_barang').removeAttr('max');
        }

        function nextCode() {
            return $.getJSON(baseUrl + '/next-code').done(function (response) {
                $('#kd_barang_out').val(response.data.kd_barang_out);
            });
        }

        function resetForm() {
            $form[0].reset();
            $form.attr('action', baseUrl);
            $('#outMethod').val('POST');
            $('#outSubmitAction').val('draft');
            $('#kd_barang_out').val('');
            $('#currentPhoto').empty();
            $('#editButtons').removeClass('d-none');
            $('#outForm :input').prop('disabled', false);
            $('#kd_barang_out, #nm_barang, #divisi, #nama_pic').prop('disabled', true);
            clearSource();
            clearErrors();
        }

        function presentStatus(item) {
            const last = item.approval_logs.length ? item.approval_logs[item.approval_logs.length - 1] : null;
            if (last && last.action === 'REJECTED') return 'Ditolak';
            if (item.status_approved === 'APPROVED_1') return 'Menunggu Approval Supervisor';
            if (item.status_approved === 'APPROVED_2') {
                return last && last.approval_stage === 4 && last.action === 'APPROVED'
                    ? 'Selesai'
                    : 'Menunggu Approval Petugas';
            }
            return 'Draft';
        }

        function renderRows(items) {
            const $body = $('#outTable tbody').empty();
            if (!items.length) {
                $('<tr>').append($('<td>').attr('colspan', 8).addClass('text-center text-muted')
                    .text('Belum ada permohonan pengeluaran barang.')).appendTo($body);
                return;
            }

            $.each(items, function (index, item) {
                const status = presentStatus(item);
                const statusClass = status === 'Selesai' ? 'success' : (status === 'Ditolak' ? 'danger' : (status === 'Draft' ? 'secondary' : 'info'));
                const $row = $('<tr>').attr('data-id', item.id);
                $row.append($('<td>').text(index + 1));
                $row.append($('<td>').text(item.kd_barang_out));
                $row.append($('<td>').append(document.createTextNode(item.nm_barang || '-'))
                    .append($('<br>')).append($('<small>').addClass('text-muted').text(item.kd_barang)));
                $row.append($('<td>').append(document.createTextNode(item.divisi || '-'))
                    .append($('<br>')).append($('<small>').addClass('text-muted').text(item.nama_pic || '-')));
                $row.append($('<td>').append(document.createTextNode((item.qty_barang || 0) + ' unit'))
                    .append($('<br>')).append($('<small>').text(item.tujuan_barang || '-')));
                $row.append($('<td>').append(document.createTextNode(item.tgl_permohonan_out || '-'))
                    .append($('<br>')).append($('<small>').addClass('text-muted')
                        .append($('<i>').addClass('far fa-clock mr-1'))
                        .append(document.createTextNode('Dibuat ' + (item.created_at_human || '')))));

                const $history = $('<td>').append($('<span>').addClass('badge badge-' + statusClass).text(status));
                $.each(item.approval_logs, function (_, log) {
                    const stageName = log.approval_stage === 3 ? 'Supervisor' : (log.approval_stage === 4 ? 'Petugas' : 'Proses');
                    $('<div>').addClass('small text-muted')
                        .append($('<i>').addClass('far fa-clock mr-1'))
                        .append(document.createTextNode(stageName + ': ' + log.action + ' ' + log.created_at_human + (log.comment ? ' - ' + log.comment : '')))
                        .appendTo($history);
                });
                $row.append($history);

                const $actions = $('<td>').append($('<button>').attr({ type: 'button', 'data-id': item.id })
                    .addClass('btn btn-outline-primary btn-sm inspect-out').append($('<i>').addClass('far fa-eye')).append(' Periksa'));
                if (item.can_edit) {
                    $('<button>').attr({ type: 'button', 'data-id': item.id }).addClass('btn btn-success btn-sm ml-1 edit-out')
                        .append($('<i>').addClass('fas fa-edit')).append(' Edit').appendTo($actions);
                }
                if (item.can_delete) {
                    $('<button>').attr({ type: 'button', 'data-id': item.id }).addClass('btn btn-danger btn-sm ml-1 delete-out')
                        .append($('<i>').addClass('fas fa-trash-alt')).append(' Hapus').appendTo($actions);
                }
                $row.append($actions).appendTo($body);
            });
        }

        function refreshRows() {
            return $.getJSON(dataUrl).done(function (response) {
                renderRows(response.data);
            }).fail(function () {
                showMessage('Daftar pengeluaran gagal dimuat ulang.', 'danger');
            });
        }

        function loadSource(code) {
            if (!code) {
                clearSource();
                return;
            }
            $.getJSON(lookupUrl + '/' + encodeURIComponent(code))
                .done(function (response) {
                    setSource(response.data);
                })
                .fail(function (xhr) {
                    clearSource();
                    const text = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message : 'Kode barang tidak ditemukan atau belum disetujui.';
                    $('#kdBarangError').removeClass('d-none').text(text);
                });
        }

        $('#createOutRequest').on('click', function () {
            resetForm();
            $('#outFormModalLabel').text('Tambah Pengeluaran Barang');
            nextCode();
            $('#outFormModal').modal('show');
        });

        $('#kd_barang').on('change blur', function () {
            const code = $(this).val().trim();
            if (!activeSource || activeSource.kd_barang !== code) {
                clearErrors();
                loadSource(code);
            }
        });

        $('#qty_barang').on('input change', function (event) {
            const quantity = Number($(this).val());
            const exceedsStock = activeSource && quantity > activeSource.stock_tersedia;
            $('#qtyError').toggleClass('d-none', !exceedsStock)
                .text(exceedsStock ? 'Stock Barang Tidak Sesuai/Habis!!!' : '');
            if (exceedsStock && event.type === 'change') {
                window.alert('Stock Barang Tidak Sesuai/Habis!!!');
            }
        });

        $('.save-out').on('click', function () {
            $('#outSubmitAction').val($(this).data('action'));
            $form.trigger('submit');
        });

        $form.on('submit', function (event) {
            event.preventDefault();
            clearErrors();
            if (!activeSource) {
                $('#kdBarangError').removeClass('d-none').text('Masukkan kode barang yang valid terlebih dahulu.');
                return;
            }
            if (Number($('#qty_barang').val()) > activeSource.stock_tersedia) {
                $('#stockError').removeClass('d-none').text('Stock Barang Tidak Sesuai/Habis!!!');
                window.alert('Stock Barang Tidak Sesuai/Habis!!!');
                return;
            }

            const $buttons = $('.save-out').prop('disabled', true);
            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: new FormData($form[0]),
                processData: false,
                contentType: false,
                headers: { 'Accept': 'application/json' }
            }).done(function (response) {
                $('#outFormModal').modal('hide');
                showMessage(response.message, 'success');
                refreshRows();
            }).fail(function (xhr) {
                const response = xhr.responseJSON || {};
                if (response.message && response.message.indexOf('Stock Barang Tidak Sesuai/Habis') !== -1) {
                    $('#stockError').removeClass('d-none').text('Stock Barang Tidak Sesuai/Habis!!!');
                    window.alert('Stock Barang Tidak Sesuai/Habis!!!');
                    return;
                }
                const errors = response.errors || {};
                const fields = {
                    kd_barang: '#kdBarangError',
                    tgl_permohonan_out: '#outDateError',
                    qty_barang: '#qtyError',
                    tujuan_barang: '#tujuanError',
                    foto: '#fotoError'
                };
                let shown = false;
                $.each(fields, function (field, selector) {
                    if (errors[field]) {
                        $(selector).removeClass('d-none').text(errors[field][0]);
                        shown = true;
                    }
                });
                if (!shown) {
                    $('#outFormError').removeClass('d-none').text(response.message || 'Permohonan gagal disimpan.');
                }
            }).always(function () {
                $buttons.prop('disabled', false);
            });
        });

        function openRecord(id, editable) {
            $.getJSON(baseUrl + '/' + id).done(function (response) {
                const item = response.data;
                resetForm();
                activeRequest = item;
                setSource({
                    kd_barang: item.kd_barang,
                    nm_barang: item.nm_barang,
                    divisi: item.divisi,
                    nama_pic: item.nama_pic,
                    stock: item.qty_barang,
                    stock_tersedia: Number(item.stock_tersedia || item.qty_barang)
                });
                $('#kd_barang_out').val(item.kd_barang_out);
                $('#tgl_permohonan_out').val(item.tgl_permohonan_out);
                $('#qty_barang').val(item.qty_barang);
                $('#tujuan_barang').val(item.tujuan_barang);
                $('#outMethod').val(editable ? 'PUT' : 'GET');
                if (editable) {
                    $form.attr('action', baseUrl + '/' + id);
                    $('#outSubmitAction').val('draft');
                    $('#outFormModalLabel').text('Edit Draft Pengeluaran Barang');
                } else {
                    $('#outFormModalLabel').text('Periksa Permohonan Pengeluaran Barang');
                    $('#editButtons').addClass('d-none');
                    $('#outForm :input').not('[name="_token"]').prop('disabled', true);
                    $('#kd_barang_out, #nm_barang, #divisi, #nama_pic').prop('disabled', true);
                    if (item.foto_url) {
                        $('<a>').attr({ href: item.foto_url, target: '_blank', rel: 'noopener' })
                            .text('Lihat lampiran foto').appendTo('#currentPhoto');
                    }
                }
                if (editable && item.foto_url) {
                    $('<a>').attr({ href: item.foto_url, target: '_blank', rel: 'noopener' })
                        .text('Lihat foto saat ini').appendTo('#currentPhoto');
                }
                $('#outFormModal').modal('show');
            }).fail(function () {
                showMessage('Permohonan pengeluaran gagal dimuat.', 'danger');
            });
        }

        $('#outTable').on('click', '.edit-out', function () {
            openRecord($(this).data('id'), true);
        });
        $('#outTable').on('click', '.inspect-out', function () {
            openRecord($(this).data('id'), false);
        });
        $('#outTable').on('click', '.delete-out', function () {
            const id = $(this).data('id');
            if (!window.confirm('Hapus draft pengeluaran barang ini?')) return;
            $.ajax({
                url: baseUrl + '/' + id,
                method: 'POST',
                data: { _token: $form.find('[name="_token"]').val(), _method: 'DELETE' },
                headers: { 'Accept': 'application/json' }
            }).done(function (response) {
                showMessage(response.message, 'success');
                refreshRows();
            }).fail(function (xhr) {
                showMessage(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Draft gagal dihapus.', 'danger');
            });
        });

        refreshRows();
        @if ($source && auth()->user()->roles === 'admin')
            resetForm();
            nextCode();
            $('#outFormModal').modal('show');
            loadSource(@json($source->kd_barang));
        @endif
    });
</script>
@endpush
