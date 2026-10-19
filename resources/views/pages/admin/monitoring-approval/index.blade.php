@extends('layouts.main')
@section('title', 'Monitoring Approval Permohonan Barang Masuk')

@section('content')
<section class="section custom-section">
    <div class="section-body">
        <div id="monitoringMessage"></div>
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Monitoring Approval Daftar Permohonan Barang Masuk</h4>
            </div>
            <div class="card-body">
                <ul class="nav nav-pills approval-filters mb-3" role="tablist">
                    <li class="nav-item">
                        <button type="button" class="nav-link active" data-filter="all">
                            Semua <span class="badge badge-light ml-1">{{ $approvalCounts['all'] }}</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link" data-filter="waiting">
                            Menunggu Persetujuan <span class="badge badge-light ml-1">{{ $approvalCounts['waiting'] }}</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link" data-filter="approved">
                            Disetujui <span class="badge badge-light ml-1">{{ $approvalCounts['approved'] }}</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link" data-filter="rejected">
                            Ditolak <span class="badge badge-light ml-1">{{ $approvalCounts['rejected'] }}</span>
                        </button>
                    </li>
                </ul>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover approval-table">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Barang</th>
                                <th>Keterangan</th>
                                <th>Tanggal Permohonan</th>
                                <th>Approval 1</th>
                                <th>Approval 2</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($permohonanBarangMasuks as $item)
                                <tr data-monitoring-status="{{ $item->monitoring_status }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="approval-item">
                                        @if ($item->foto && (auth()->user()->roles !== 'user' || (string) $item->user_created_by === (string) auth()->id()))
                                            <a href="{{ route('permohonan-barang-masuk.attachment', [$item, 'foto']) }}" target="_blank" rel="noopener">
                                                <img src="{{ route('permohonan-barang-masuk.attachment', [$item, 'foto']) }}" alt="Foto {{ $item->nm_barang }}" class="approval-photo">
                                            </a>
                                        @else
                                            <div class="approval-photo approval-photo-empty"><i class="fas fa-box"></i></div>
                                        @endif
                                        <div>
                                            <strong>{{ $item->nm_barang ?: 'Nama barang belum diisi' }}</strong>
                                            <div class="small text-muted">Kode: {{ $item->kd_barang }}</div>
                                            <div class="small text-muted">Jumlah: {{ $item->qty_barang ?? '-' }} unit</div>
                                        </div>
                                    </td>
                                    <td class="small">
                                        @if ($item->kondisi_barang === 'RUSAK')
                                            <div class="text-danger"><i class="fas fa-circle"></i> Rusak</div>
                                        @elseif ($item->kondisi_barang === 'BAIK')
                                            <div class="text-success"><i class="fas fa-circle"></i> Baik</div>
                                        @else
                                            <div class="text-muted"><i class="fas fa-circle"></i> Kondisi belum diisi</div>
                                        @endif
                                        <div>PIC: {{ $item->nama_pic ?: '-' }}</div>
                                        <div>Divisi: {{ $item->divisi ?: '-' }}</div>
                                        <div>Lokasi: {{ $item->lokasi_penyimpanan ?: '-' }}</div>
                                    </td>
                                    <td class="small">
                                        {{ $item->tgl_permohonan?->format('d-m-Y') ?? '-' }}
                                        @if ($item->tgl_permohonan)
                                            <div>{{ $item->created_at->format('H:i') }} WIB</div>
                                        @endif
                                        <div class="text-muted"><i class="far fa-clock"></i> {{ $item->created_at->locale('id')->diffForHumans() }}</div>
                                        @foreach ($item->approvalLogs as $log)
                                            <div class="text-muted"><i class="far fa-clock"></i> {{ $log->created_at->locale('id')->diffForHumans() }}</div>
                                        @endforeach
                                    </td>
                                    <td class="text-center">
                                        @if ($item->approval1_status === 'approved')
                                            <span class="badge badge-success approval-state"><i class="fas fa-check"></i> Disetujui</span>
                                            <div class="small text-muted">{{ optional($item->approvalLogs->firstWhere('action', 'SUBMITTED')?->created_at)->format('d-m-Y H:i') }}</div>
                                        @elseif ($item->approval1_status === 'rejected')
                                            <span class="badge badge-danger approval-state"><i class="fas fa-times"></i> Ditolak</span>
                                            <div class="small text-danger">{{ optional($item->approvalLogs->where('approval_stage', 1)->where('action', 'REJECTED')->last()?->created_at)->format('d-m-Y H:i') }}</div>
                                        @else
                                            <span class="badge badge-secondary approval-state"><i class="far fa-clock"></i> Menunggu</span>
                                            <div class="small text-muted">Belum diajukan</div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($item->approval2_status === 'approved')
                                            <span class="badge badge-success approval-state"><i class="fas fa-check"></i> Disetujui</span>
                                            <div class="small text-muted">{{ optional($item->approvalLogs->where('approval_stage', 2)->where('action', 'APPROVED')->last()?->created_at)->format('d-m-Y H:i') }}</div>
                                        @elseif ($item->approval2_status === 'rejected')
                                            <span class="badge badge-danger approval-state"><i class="fas fa-times"></i> Ditolak</span>
                                            <div class="small text-danger">{{ optional($item->approvalLogs->where('approval_stage', 2)->where('action', 'REJECTED')->last()?->created_at)->format('d-m-Y H:i') }}</div>
                                        @else
                                            <span class="badge badge-secondary approval-state"><i class="far fa-clock"></i> Menunggu</span>
                                            <div class="small text-muted">----</div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-primary btn-sm inspect-request" data-id="{{ $item->id }}">
                                            <i class="far fa-eye"></i> Periksa
                                        </button>
                                        @if (auth()->user()->roles === 'admin' && $item->can_create_out && !$item->has_outgoing_request)
                                            <a href="{{ route('permohonan-barang-out.index', ['source' => $item->id]) }}"
                                                class="btn btn-outline-success btn-sm create-outgoing mt-1"
                                                data-name="{{ $item->nm_barang }}">
                                                <i class="fas fa-sign-out-alt"></i> Keluarkan
                                            </a>
                                        @elseif (auth()->user()->roles === 'admin' && $item->has_outgoing_request)
                                            <a href="{{ route('permohonan-barang-out.index') }}" class="btn btn-light btn-sm mt-1">Pengeluaran dibuat</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr class="empty-monitoring">
                                    <td colspan="7" class="text-center text-muted">Belum ada permohonan barang masuk.</td>
                                </tr>
                            @endforelse
                            <tr id="noFilteredResults" class="d-none">
                                <td colspan="7" class="text-center text-muted">Tidak ada permohonan pada kategori ini.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="inspectModal" tabindex="-1" role="dialog" aria-labelledby="inspectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="inspectModalLabel">Periksa Permohonan Barang Masuk</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="inspectLoading" class="text-center text-muted py-4">Memuat detail permohonan...</div>
                <div id="inspectError" class="alert alert-danger d-none"></div>
                <div id="inspectDetails" class="d-none">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <img id="inspectPhoto" class="img-fluid rounded d-none" alt="Foto barang" style="max-height: 220px;">
                            <div id="inspectPhotoEmpty" class="approval-photo approval-photo-empty mx-auto"><i class="fas fa-box"></i></div>
                        </div>
                        <div class="col-md-8">
                            <h5 id="inspectItemName"></h5>
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Kode Barang</dt><dd class="col-sm-8" id="inspectCode"></dd>
                                <dt class="col-sm-4">Tanggal Permohonan</dt><dd class="col-sm-8" id="inspectDate"></dd>
                                <dt class="col-sm-4">PIC / Divisi</dt><dd class="col-sm-8" id="inspectPic"></dd>
                                <dt class="col-sm-4">Lokasi</dt><dd class="col-sm-8" id="inspectLocation"></dd>
                                <dt class="col-sm-4">Kondisi / Jumlah</dt><dd class="col-sm-8" id="inspectCondition"></dd>
                                <dt class="col-sm-4">Asal Barang</dt><dd class="col-sm-8" id="inspectOrigin"></dd>
                                <dt class="col-sm-4">Lampiran</dt><dd class="col-sm-8" id="inspectAttachments"></dd>
                            </dl>
                        </div>
                    </div>
                    <hr>
                    <h6>Riwayat Approval</h6>
                    <div id="inspectHistory" class="small"></div>
                    <div id="inspectRejectComment" class="form-group d-none mt-3">
                        <label for="rejectComment">Komentar penolakan <span class="text-danger">*</span></label>
                        <textarea id="rejectComment" class="form-control" rows="3" maxlength="5000" placeholder="Tuliskan alasan penolakan"></textarea>
                        <small id="rejectCommentError" class="text-danger"></small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <div id="inspectDecisionButtons" class="d-none">
                    <button type="button" class="btn btn-success" id="approveDecision"><i class="fas fa-check"></i> Approved</button>
                    <button type="button" class="btn btn-danger" id="showRejectComment"><i class="fas fa-times"></i> Reject</button>
                </div>
                <div id="rejectDecisionButtons" class="d-none">
                    <button type="button" class="btn btn-outline-secondary" id="cancelReject">Batal</button>
                    <button type="button" class="btn btn-danger" id="confirmReject">OK</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
<style>
    .approval-filters .nav-link {
        border: 1px solid #e3e8ef;
        margin-right: .5rem;
        padding: .35rem .8rem;
        color: #34395e;
        background: #fff;
    }

    .approval-filters .nav-link.active {
        color: #fff;
        background: #6777ef;
        border-color: #6777ef;
    }

    .approval-table thead th {
        background: #f4f8ff;
        color: #34395e;
        font-size: .75rem;
        vertical-align: middle;
    }

    .approval-table td {
        vertical-align: middle;
    }

    .approval-item {
        min-width: 180px;
    }

    .approval-photo {
        width: 54px;
        height: 54px;
        object-fit: cover;
        border-radius: 50%;
        display: inline-block;
        margin-right: .5rem;
        vertical-align: middle;
    }

    .approval-photo-empty {
        background: #edf1f7;
        color: #8a94a6;
        font-size: 1.35rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .approval-state {
        min-width: 88px;
        padding: .4rem .5rem;
    }
</style>
@endpush

@push('script')
<script>
    $(function () {
        const baseUrl = @json(url('/permohonan-barang-masuk'));
        const csrfToken = @json(csrf_token());
        let activeRequestId = null;

        const savedMessage = sessionStorage.getItem('permohonanApprovalMessage');
        if (savedMessage) {
            $('#monitoringMessage').html($('<div>').addClass('alert alert-success alert-dismissible fade show')
                .append(document.createTextNode(savedMessage))
                .append('<button type="button" class="close" data-dismiss="alert" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>'));
            sessionStorage.removeItem('permohonanApprovalMessage');
        }

        function applyFilter(filter) {
            const $rows = $('.approval-table tbody tr[data-monitoring-status]');
            let visible = 0;

            $rows.each(function () {
                const matches = filter === 'all' || $(this).data('monitoring-status') === filter;
                $(this).toggle(matches);
                if (matches) {
                    visible++;
                }
            });

            $('#noFilteredResults').toggleClass('d-none', visible > 0 || $rows.length === 0);
            $('.approval-filters .nav-link').removeClass('active')
                .filter('[data-filter="' + filter + '"]').addClass('active');
            localStorage.setItem('permohonanApprovalFilter', filter);
        }

        $('.approval-filters').on('click', '[data-filter]', function () {
            applyFilter($(this).data('filter'));
        });
        applyFilter(localStorage.getItem('permohonanApprovalFilter') || 'all');

        function appendHistory(logs, createdAt) {
            const $history = $('#inspectHistory').empty();
            if (createdAt) {
                $('<div>').append($('<i>').addClass('far fa-clock mr-1'))
                    .append(document.createTextNode('Dibuat ' + createdAt))
                    .appendTo($history);
            }

            $.each(logs || [], function (_, log) {
                const labels = {
                    SUBMITTED: 'Diajukan',
                    APPROVED: 'Disetujui',
                    REJECTED: 'Ditolak'
                };
                const $line = $('<div>').append($('<i>').addClass('far fa-clock mr-1'))
                    .append(document.createTextNode((labels[log.action] || log.action) + ' ' + log.created_at_human));
                if (log.comment) {
                    $line.append(document.createTextNode(': ' + log.comment));
                }
                $line.appendTo($history);
            });
        }

        function openInspection(id) {
            activeRequestId = id;
            $('#inspectLoading').removeClass('d-none');
            $('#inspectError, #inspectDetails, #inspectDecisionButtons, #rejectDecisionButtons, #inspectRejectComment').addClass('d-none');
            $('#rejectComment, #rejectCommentError').val('').empty();
            $('#inspectModal').modal('show');

            $.getJSON(baseUrl + '/' + id).done(function (response) {
                const item = response.data;
                $('#inspectLoading').addClass('d-none');
                $('#inspectDetails').removeClass('d-none');
                $('#inspectItemName').text(item.nm_barang || 'Nama barang belum diisi');
                $('#inspectCode').text(item.kd_barang || '-');
                $('#inspectDate').text(item.tgl_permohonan || '-');
                $('#inspectPic').text((item.nama_pic || '-') + ' / ' + (item.divisi || '-'));
                $('#inspectLocation').text(item.lokasi_penyimpanan || '-');
                $('#inspectCondition').text((item.kondisi_barang || '-') + ' / ' + (item.qty_barang || '-') + ' unit');
                $('#inspectOrigin').text(item.asal_barang || '-');
                $('#inspectAttachments').empty();

                if (item.foto_url) {
                    $('#inspectPhoto').attr('src', item.foto_url).removeClass('d-none');
                    $('#inspectPhotoEmpty').addClass('d-none');
                } else {
                    $('#inspectPhoto').addClass('d-none').attr('src', '');
                    $('#inspectPhotoEmpty').removeClass('d-none');
                }

                if (item.bast_url) {
                    $('<a>').attr({ href: item.bast_url, target: '_blank', rel: 'noopener' }).text('Lihat BAST PDF').appendTo('#inspectAttachments');
                }
                if (item.foto_url) {
                    if (item.bast_url) {
                        $('#inspectAttachments').append(' / ');
                    }
                    $('<a>').attr({ href: item.foto_url, target: '_blank', rel: 'noopener' }).text('Lihat foto').appendTo('#inspectAttachments');
                }
                if (!item.bast_url && !item.foto_url) {
                    $('#inspectAttachments').text('-');
                }

                appendHistory(item.approval_logs, item.created_at_human);
                if (item.can_approve) {
                    $('#inspectDecisionButtons').removeClass('d-none');
                }
            }).fail(function () {
                $('#inspectLoading').addClass('d-none');
                $('#inspectError').removeClass('d-none').text('Detail permohonan gagal dimuat.');
            });
        }

        $('.approval-table').on('click', '.inspect-request', function () {
            openInspection($(this).data('id'));
        });

        $('.approval-table').on('click', '.create-outgoing', function (event) {
            if (!window.confirm('Apakah barang Ini Mau dikeluarkan??')) {
                event.preventDefault();
            }
        });

        function submitDecision(decision, comment) {
            $.ajax({
                url: baseUrl + '/' + activeRequestId + '/approve/1',
                method: 'PATCH',
                data: { _token: csrfToken, decision: decision, comment: comment || '' },
                headers: { 'Accept': 'application/json' }
            }).done(function (response) {
                $('#inspectModal').modal('hide');
                sessionStorage.setItem('permohonanApprovalMessage', response.message);
                window.location.reload();
            }).fail(function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.comment) {
                    $('#rejectCommentError').text(xhr.responseJSON.errors.comment[0]);
                    return;
                }
                const message = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'Keputusan approval gagal disimpan.';
                $('#inspectError').removeClass('d-none').text(message);
            }).always(function () {
                $('#approveDecision, #confirmReject').prop('disabled', false);
            });
        }

        $('#approveDecision').on('click', function () {
            $(this).prop('disabled', true);
            submitDecision('approved', '');
        });

        $('#showRejectComment').on('click', function () {
            $('#inspectDecisionButtons').addClass('d-none');
            $('#inspectRejectComment, #rejectDecisionButtons').removeClass('d-none');
            $('#rejectComment').trigger('focus');
        });

        $('#cancelReject').on('click', function () {
            $('#inspectRejectComment, #rejectDecisionButtons').addClass('d-none');
            $('#inspectDecisionButtons').removeClass('d-none');
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
            submitDecision('reject', comment);
        });

        $('#inspectModal').on('hidden.bs.modal', function () {
            activeRequestId = null;
            $('#approveDecision, #confirmReject').prop('disabled', false);
        });
    });
</script>
@endpush
