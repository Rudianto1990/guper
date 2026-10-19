@extends('layouts.main')
@section('title', 'Permohonan Barang Masuk')

@section('content')
<section class="section custom-section">
    <div class="section-body">
        <div id="pageMessage"></div>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4>Permohonan Barang Masuk</h4>
                <button type="button" class="btn btn-primary" id="addPermohonan">
                    <i class="fas fa-plus"></i> Tambah Permohonan
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="permohonanTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode / Tanggal</th>
                                <th>Nama Barang</th>
                                <th>PIC / Divisi</th>
                                <th>Lokasi Penyimpanan</th>
                                <th>Qty</th>
                                <th>Kondisi</th>
                                <th>Status</th>
                                <th>Lampiran</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($permohonanBarangMasuks as $item)
                                <tr data-id="{{ $item->id }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        {{ $item->kd_barang }}<br>
                                        <small class="text-muted">{{ $item->tgl_permohonan?->format('d-m-Y') ?? '-' }}</small>
                                        <div class="small text-muted"><i class="far fa-clock"></i> Dibuat {{ $item->created_at->locale('id')->diffForHumans() }}</div>
                                        @foreach ($item->approvalLogs as $log)
                                            <div class="small {{ $log->action === 'REJECTED' ? 'text-danger' : 'text-muted' }}">
                                                <i class="far fa-clock"></i>
                                                @if ($log->action === 'SUBMITTED')
                                                    Diajukan {{ $log->created_at->locale('id')->diffForHumans() }}
                                                @elseif ($log->action === 'APPROVED')
                                                    Disetujui {{ $log->created_at->locale('id')->diffForHumans() }}
                                                @else
                                                    Ditolak {{ $log->created_at->locale('id')->diffForHumans() }}: {{ $log->comment }}
                                                @endif
                                            </div>
                                        @endforeach
                                    </td>
                                    <td>{{ $item->nm_barang }}</td>
                                    <td>{{ $item->nama_pic }}<br><small class="text-muted">{{ $item->divisi }}</small></td>
                                    <td>{{ $item->lokasi_penyimpanan }}</td>
                                    <td>{{ $item->qty_barang }}</td>
                                    <td><span class="badge badge-{{ $item->kondisi_barang === 'BAIK' ? 'success' : 'danger' }}">{{ $item->kondisi_barang }}</span></td>
                                    <td><span class="badge badge-info">{{ $item->status_approved }}</span></td>
                                    <td>
                                        @if ($item->bast)
                                            <a href="{{ route('permohonan-barang-masuk.attachment', [$item, 'bast']) }}" target="_blank" rel="noopener">BAST</a>
                                        @endif
                                        @if ($item->foto)
                                            <a href="{{ route('permohonan-barang-masuk.attachment', [$item, 'foto']) }}" target="_blank" rel="noopener">Foto</a>
                                        @endif
                                        @if (!$item->bast && !$item->foto)
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ((string) $item->user_created_by === (string) auth()->id() && $item->status_approved === 'DRAFT')
                                            <button type="button" class="btn btn-success btn-sm edit-permohonan" data-id="{{ $item->id }}" aria-label="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        @endif
                                        @if ($isAdmin || ((string) $item->user_created_by === (string) auth()->id() && $item->status_approved === 'DRAFT'))
                                            <button type="button" class="btn btn-danger btn-sm delete-permohonan ml-1" data-id="{{ $item->id }}" aria-label="Hapus">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr class="empty-row"><td colspan="10" class="text-center text-muted">Belum ada permohonan barang masuk.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="permohonanModal" tabindex="-1" role="dialog" aria-labelledby="permohonanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="permohonanModalLabel">Tambah Permohonan Barang Masuk</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                @include('pages.admin.permohonan-barang-masuk._form')
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="approvalModal" tabindex="-1" role="dialog" aria-labelledby="approvalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approvalModalLabel">Keputusan Approval</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p id="approvalItemName" class="font-weight-bold"></p>
                <div id="rejectCommentSection" class="d-none">
                    <label for="rejectComment">Komentar penolakan <span class="text-danger">*</span></label>
                    <textarea id="rejectComment" class="form-control" rows="4" maxlength="5000" placeholder="Tuliskan alasan penolakan"></textarea>
                    <small id="rejectCommentError" class="text-danger"></small>
                </div>
            </div>
            <div class="modal-footer">
                <div id="approvalChoices">
                    <button type="button" class="btn btn-success" id="approveDecision">Approved</button>
                    <button type="button" class="btn btn-danger" id="showRejectComment">Reject</button>
                </div>
                <div id="rejectActions" class="d-none">
                    <button type="button" class="btn btn-secondary" id="cancelReject">Batal</button>
                    <button type="button" class="btn btn-danger" id="confirmReject">OK</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    $(function () {
        const baseUrl = @json(url('/permohonan-barang-masuk'));
        const dataUrl = @json(route('permohonan-barang-masuk.data'));
        const $form = $('#permohonanForm');
        let approvalItemId = null;
        const formFields = [
            'tgl_permohonan', 'nm_barang', 'nama_pic', 'divisi', 'lokasi_penyimpanan',
            'kondisi_barang', 'qty_barang', 'asal_barang'
        ];

        function clearErrors() {
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('[data-error-for]').text('');
            $form.find('.ajax-errors').addClass('d-none').empty();
        }

        function showErrors(errors) {
            const messages = [];
            $.each(errors || {}, function (field, fieldMessages) {
                const message = fieldMessages[0];
                const $input = $form.find('[name="' + field + '"]');
                if ($input.length) {
                    $input.addClass('is-invalid');
                }
                $form.find('[data-error-for="' + field + '"]').text(message);
                messages.push(message);
            });

            if (!messages.length) {
                messages.push('Permintaan tidak dapat diproses. Silakan coba lagi.');
            }
            $form.find('.ajax-errors').removeClass('d-none').text(messages.join(' '));
        }

        function createCell(value) {
            return $('<td>').text(value || '-');
        }

        function createFileLinks(item) {
            const $cell = $('<td>');
            if (item.bast_url) {
                $('<a>').attr({ href: item.bast_url, target: '_blank', rel: 'noopener' }).text('BAST').appendTo($cell);
            }
            if (item.bast_url && item.foto_url) {
                $cell.append(' / ');
            }
            if (item.foto_url) {
                $('<a>').attr({ href: item.foto_url, target: '_blank', rel: 'noopener' }).text('Foto').appendTo($cell);
            }
            if (!item.bast_url && !item.foto_url) {
                $cell.text('-');
            }
            return $cell;
        }

        function appendRelativeTime($cell, label, relativeTime, comment) {
            const $line = $('<div>').addClass('small text-muted')
                .append($('<i>').addClass('far fa-clock mr-1'))
                .append(document.createTextNode(label + ' ' + relativeTime));
            if (comment) {
                $line.append(document.createTextNode(': ' + comment));
            }
            $cell.append($line);
        }

        function appendApprovalHistory($cell, item) {
            appendRelativeTime($cell, 'Dibuat', item.created_at_human, null);
            $.each(item.approval_logs || [], function (_, log) {
                const labels = {
                    SUBMITTED: 'Diajukan',
                    APPROVED: 'Disetujui',
                    REJECTED: 'Ditolak'
                };
                appendRelativeTime($cell, labels[log.action] || log.action, log.created_at_human, log.comment);
            });
        }

        function renderRows(items) {
            const $body = $('#permohonanTable tbody').empty();
            if (!items.length) {
                $('<tr>').addClass('empty-row')
                    .append($('<td>').attr('colspan', 10).addClass('text-center text-muted').text('Belum ada permohonan barang masuk.'))
                    .appendTo($body);
                return;
            }

            $.each(items, function (index, item) {
                const $row = $('<tr>').attr('data-id', item.id);
                $row.append(createCell(index + 1));
                const $dateCell = $('<td>').append(document.createTextNode(item.kd_barang || '-')).append('<br>')
                    .append($('<small>').addClass('text-muted').text(item.tgl_permohonan || '-'));
                appendApprovalHistory($dateCell, item);
                $row.append($dateCell);
                $row.append(createCell(item.nm_barang));
                $row.append($('<td>').append(document.createTextNode(item.nama_pic || '-')).append('<br>')
                    .append($('<small>').addClass('text-muted').text(item.divisi || '-')));
                $row.append(createCell(item.lokasi_penyimpanan));
                $row.append(createCell(item.qty_barang));

                const conditionClass = item.kondisi_barang === 'BAIK' ? 'success' : 'danger';
                $row.append($('<td>').append($('<span>').addClass('badge badge-' + conditionClass).text(item.kondisi_barang)));
                $row.append($('<td>').append($('<span>').addClass('badge badge-info').text(item.status_approved)));
                $row.append(createFileLinks(item));

                const $actions = $('<td>');
                if (item.can_approve) {
                    $('<button>').attr({ type: 'button', 'data-id': item.id })
                        .addClass('btn btn-primary btn-sm approve-permohonan').text('Approve').appendTo($actions);
                }
                if (item.can_edit) {
                    $('<button>').attr({ type: 'button', 'data-id': item.id, 'aria-label': 'Edit' })
                        .addClass('btn btn-success btn-sm edit-permohonan').append($('<i>').addClass('fas fa-edit')).appendTo($actions);
                }
                if (item.can_delete) {
                    $('<button>').attr({ type: 'button', 'data-id': item.id, 'aria-label': 'Hapus' })
                        .addClass('btn btn-danger btn-sm ml-1 delete-permohonan').append($('<i>').addClass('fas fa-trash-alt')).appendTo($actions);
                }
                $row.append($actions).appendTo($body);
            });
        }

        function refreshRows() {
            return $.getJSON(dataUrl).done(function (response) {
                renderRows(response.data);
            }).fail(function () {
                $('#pageMessage').html('<div class="alert alert-danger">Daftar permohonan gagal dimuat ulang.</div>');
            });
        }

        function resetForm() {
            $form[0].reset();
            clearErrors();
            $('#permohonanMethod').val('POST');
            $('#submitAction').val('draft');
            $form.attr('action', baseUrl);
            $('#currentBast, #currentFoto').empty();
            $form.find(':submit').prop('disabled', false);
        }

        $('#addPermohonan').on('click', function () {
            resetForm();
            $('#permohonanModalLabel').text('Tambah Permohonan Barang Masuk');
            $('#permohonanModal').modal('show');
        });

        $form.on('click', '#saveDraft', function () {
            $('#submitAction').val('draft');
        });

        $form.on('click', '#submitForApproval', function () {
            $('#submitAction').val('submit');
        });

        $(document).on('click', '.edit-permohonan', function () {
            const id = $(this).data('id');
            resetForm();
            $.getJSON(baseUrl + '/' + id).done(function (response) {
                const item = response.data;
                $.each(formFields, function (_, field) {
                    $form.find('[name="' + field + '"]').val(item[field] || '');
                });
                $('#permohonanMethod').val('PUT');
                $form.attr('action', baseUrl + '/' + id);
                if (item.bast_url) {
                    $('<a>').attr({ href: item.bast_url, target: '_blank', rel: 'noopener' }).text('Lihat BAST saat ini').appendTo('#currentBast');
                }
                if (item.foto_url) {
                    $('<a>').attr({ href: item.foto_url, target: '_blank', rel: 'noopener' }).text('Lihat foto saat ini').appendTo('#currentFoto');
                }
                $('#permohonanModalLabel').text('Edit Permohonan Barang Masuk');
                $('#permohonanModal').modal('show');
            }).fail(function () {
                $('#pageMessage').html('<div class="alert alert-danger">Data permohonan gagal dimuat.</div>');
            });
        });

        $form.on('submit', function (event) {
            event.preventDefault();
            clearErrors();
            const $submit = $form.find(':submit').prop('disabled', true);

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: new FormData($form[0]),
                processData: false,
                contentType: false,
                headers: { 'Accept': 'application/json' }
            }).done(function (response) {
                $('#permohonanModal').modal('hide');
                $('#pageMessage').html($('<div>').addClass('alert alert-success alert-dismissible fade show')
                    .append(document.createTextNode(response.message))
                    .append('<button type="button" class="close" data-dismiss="alert" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>'));
                refreshRows();
            }).fail(function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON) {
                    showErrors(xhr.responseJSON.errors);
                } else {
                    showErrors({});
                }
            }).always(function () {
                $submit.prop('disabled', false);
            });
        });

            function submitApprovalDecision(decision, comment) {
                $.ajax({
                    url: baseUrl + '/' + approvalItemId + '/approve',
                    method: 'PATCH',
                    data: {
                        _token: $form.find('[name="_token"]').val(),
                        decision: decision,
                        comment: comment || ''
                    },
                    headers: { 'Accept': 'application/json' }
                }).done(function (response) {
                    $('#approvalModal').modal('hide');
                    $('#pageMessage').html($('<div>').addClass('alert alert-success alert-dismissible fade show')
                        .append(document.createTextNode(response.message))
                        .append('<button type="button" class="close" data-dismiss="alert" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>'));
                    refreshRows();
                }).fail(function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.comment) {
                        $('#rejectCommentError').text(xhr.responseJSON.errors.comment[0]);
                        return;
                    }
                    const message = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'Keputusan approval gagal disimpan.';
                    $('#pageMessage').html($('<div>').addClass('alert alert-danger').text(message));
                }).always(function () {
                    $('#approveDecision, #confirmReject').prop('disabled', false);
                });
            }

            $(document).on('click', '.approve-permohonan', function () {
                approvalItemId = $(this).data('id');
                $('#approvalItemName').text($(this).closest('tr').find('td').eq(2).text());
                $('#rejectComment').val('');
                $('#rejectCommentError').empty();
                $('#rejectCommentSection, #rejectActions').addClass('d-none');
                $('#approvalChoices').removeClass('d-none');
                $('#approvalModal').modal('show');
            });

            $('#approveDecision').on('click', function () {
                $(this).prop('disabled', true);
                submitApprovalDecision('approved', '');
            });

            $('#showRejectComment').on('click', function () {
                $('#approvalChoices').addClass('d-none');
                $('#rejectCommentSection, #rejectActions').removeClass('d-none');
                $('#rejectComment').trigger('focus');
            });

            $('#cancelReject').on('click', function () {
                $('#rejectCommentSection, #rejectActions').addClass('d-none');
                $('#approvalChoices').removeClass('d-none');
                $('#rejectCommentError').empty();
            });

            $('#confirmReject').on('click', function () {
                const comment = $('#rejectComment').val().trim();
                $('#rejectCommentError').empty();
                if (!comment) {
                    $('#rejectCommentError').text('Komentar penolakan wajib diisi.');
                    return;
                }
                $(this).prop('disabled', true);
                submitApprovalDecision('reject', comment);
            });

            $('#approvalModal').on('hidden.bs.modal', function () {
                approvalItemId = null;
                $('#approveDecision, #confirmReject').prop('disabled', false);
                $('#rejectComment').val('');
                $('#rejectCommentError').empty();
                $('#rejectCommentSection, #rejectActions').addClass('d-none');
                $('#approvalChoices').removeClass('d-none');
            });

            $(document).on('click', '.delete-permohonan', function () {
            const id = $(this).data('id');
            if (!window.confirm('Hapus permohonan barang masuk ini?')) {
                return;
            }

            $.ajax({
                url: baseUrl + '/' + id,
                method: 'POST',
                data: { _token: $form.find('[name="_token"]').val(), _method: 'DELETE' },
                headers: { 'Accept': 'application/json' }
            }).done(function (response) {
                $('#pageMessage').html($('<div>').addClass('alert alert-success alert-dismissible fade show')
                    .append(document.createTextNode(response.message))
                    .append('<button type="button" class="close" data-dismiss="alert" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>'));
                refreshRows();
            }).fail(function () {
                $('#pageMessage').html('<div class="alert alert-danger">Permohonan gagal dihapus.</div>');
            });
        });

        $('#permohonanModal').on('hidden.bs.modal', resetForm);
    });
</script>
@endpush
