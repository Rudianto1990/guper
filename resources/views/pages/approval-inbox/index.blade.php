@extends('layouts.main')
@section('title', $inboxTitle)

@section('content')
<section class="section custom-section">
    <div class="section-body">
        <div id="inboxMessage"></div>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4>{{ $inboxTitle }}</h4>
                <span class="badge badge-primary">{{ $inboxType === 'incoming' ? $permohonanMasuk->count() : ($inboxType === 'supervisor' ? $permohonanMasuk->count() + $permohonanOut->count() : $permohonanOut->count()) }} menunggu</span>
            </div>
            <div class="card-body">
                @if (in_array($inboxType, ['incoming', 'supervisor'], true))
                    <div class="table-responsive">
                        <table class="table table-striped" id="approvalInboxTable">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Kode / Barang</th>
                                    <th>Pemohon</th>
                                    <th>Detail</th>
                                    <th>Tanggal Pengajuan</th>
                                    <th>Lampiran</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($permohonanMasuk as $item)
                                    <tr data-id="{{ $item->id }}">
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <strong>{{ $item->kd_barang }}</strong>
                                            <div>{{ $item->nm_barang ?: 'Nama barang belum diisi' }}</div>
                                            @if ($item->foto)
                                                <img src="{{ route('permohonan-barang-masuk.attachment', [$item, 'foto']) }}" alt="Foto barang" class="inbox-photo mt-2">
                                            @endif
                                        </td>
                                        <td>{{ $item->creator?->name ?? '-' }}<br><small class="text-muted">{{ $item->nama_pic ?: '-' }} / {{ $item->divisi ?: '-' }}</small></td>
                                        <td>
                                            Lokasi: {{ $item->lokasi_penyimpanan ?: '-' }}<br>
                                            Jumlah: {{ $item->qty_barang ?? '-' }}<br>
                                            Kondisi: {{ $item->kondisi_barang ?: '-' }}<br>
                                            Asal: {{ $item->asal_barang ?: '-' }}
                                        </td>
                                        <td>
                                            {{ $item->tgl_permohonan?->format('d-m-Y') ?? '-' }}
                                            <div class="small text-muted"><i class="far fa-clock"></i> {{ $item->created_at->locale('id')->diffForHumans() }}</div>
                                            <div class="small text-muted">Menunggu {{ $item->updated_at->locale('id')->diffForHumans() }}</div>
                                        </td>
                                        <td>
                                            @if ($item->bast)
                                                <a href="{{ route('permohonan-barang-masuk.attachment', [$item, 'bast']) }}" target="_blank" rel="noopener">BAST PDF</a>
                                            @else
                                                <span class="text-muted">BAST belum ada</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-success btn-sm inbox-decision"
                                                data-id="{{ $item->id }}"
                                                data-name="{{ $item->nm_barang }}"
                                                data-url="{{ route('permohonan-barang-masuk.approve', [$item, $stage]) }}">
                                                <i class="fas fa-check"></i> Approve / Reject
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted">Tidak ada pesan approval baru.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($inboxType === 'supervisor')
                        <h5 class="mt-4">Persetujuan Pengeluaran Barang</h5>
                        <div class="table-responsive">
                            <table class="table table-striped" id="approvalOutgoingInboxTable">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Kode Keluar / Barang</th>
                                        <th>PIC / Divisi</th>
                                        <th>Jumlah / Tujuan</th>
                                        <th>Tanggal Keluar</th>
                                        <th>Lampiran</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($permohonanOut as $item)
                                        <tr data-id="{{ $item->id }}">
                                            <td>{{ $loop->iteration }}</td>
                                            <td><strong>{{ $item->kd_barang_out }}</strong><div>{{ $item->nm_barang }}</div><small>{{ $item->kd_barang }}</small></td>
                                            <td>{{ $item->nama_pic }} / {{ $item->divisi }}</td>
                                            <td>{{ $item->qty_barang }} unit<br>{{ $item->tujuan_barang }}</td>
                                            <td>{{ $item->tgl_permohonan_out?->format('d-m-Y') ?? '-' }}<div class="small text-muted"><i class="far fa-clock"></i> {{ $item->created_at->locale('id')->diffForHumans() }}</div></td>
                                            <td>
                                                @if ($item->foto)
                                                    <a href="{{ route('permohonan-barang-out.attachment', [$item, 'foto']) }}" target="_blank" rel="noopener">Foto</a>
                                                @endif
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-success btn-sm inbox-decision"
                                                    data-id="{{ $item->id }}"
                                                    data-name="{{ $item->nm_barang }}"
                                                    data-url="{{ route('permohonan-barang-out.decide', $item) }}">
                                                    <i class="fas fa-check"></i> Approve / Reject
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-center text-muted">Tidak ada permohonan pengeluaran yang menunggu approval.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endif
                @else
                    <div class="table-responsive">
                        <table class="table table-striped" id="approvalInboxTable">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Kode Keluar / Barang</th>
                                    <th>Pemohon</th>
                                    <th>Detail</th>
                                    <th>Tanggal Keluar</th>
                                    <th>Lampiran</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($permohonanOut as $item)
                                    <tr data-id="{{ $item->id }}">
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <strong>{{ $item->kd_barang_out }}</strong>
                                            <div>{{ $item->nm_barang }}</div>
                                            @if ($item->foto)
                                                <img src="{{ route('permohonan-barang-out.attachment', [$item, 'foto']) }}" alt="Foto barang" class="inbox-photo mt-2">
                                            @endif
                                        </td>
                                        <td>{{ $item->nama_pic }}<br><small class="text-muted">{{ $item->divisi }}</small></td>
                                        <td>
                                            Kode barang: {{ $item->kd_barang }}<br>
                                            Lokasi: {{ $item->lokasi_penyimpanan }}<br>
                                            Jumlah: {{ $item->qty_barang }}<br>
                                            Kondisi: {{ $item->kondisi_barang }}
                                        </td>
                                        <td>
                                            {{ $item->tgl_permohonan_out?->format('d-m-Y') ?? '-' }}
                                            <div class="small text-muted"><i class="far fa-clock"></i> {{ $item->created_at->locale('id')->diffForHumans() }}</div>
                                        </td>
                                        <td>
                                            @if ($item->bast)
                                                <a href="{{ route('permohonan-barang-out.attachment', [$item, 'bast']) }}" target="_blank" rel="noopener">BAST PDF</a>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-success btn-sm inbox-decision"
                                                data-id="{{ $item->id }}"
                                                data-name="{{ $item->nm_barang }}"
                                                data-url="{{ route('permohonan-barang-out.decide', $item) }}">
                                                <i class="fas fa-check"></i> Approve / Reject
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted">Tidak ada pesan approval baru.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="inboxDecisionModal" tabindex="-1" role="dialog" aria-labelledby="inboxDecisionTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="inboxDecisionTitle">Proses Approval</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p id="inboxItemName" class="font-weight-bold"></p>
                <div id="inboxRejectComment" class="d-none">
                    <label for="inboxComment">Komentar penolakan <span class="text-danger">*</span></label>
                    <textarea id="inboxComment" class="form-control" rows="4" maxlength="5000"></textarea>
                    <small id="inboxCommentError" class="text-danger"></small>
                </div>
                <div id="inboxDecisionError" class="alert alert-danger d-none mt-2"></div>
            </div>
            <div class="modal-footer">
                <div id="inboxChoiceButtons">
                    <button type="button" class="btn btn-success" id="inboxApprove">Approve</button>
                    <button type="button" class="btn btn-danger" id="inboxReject">Reject</button>
                </div>
                <div id="inboxRejectButtons" class="d-none">
                    <button type="button" class="btn btn-secondary" id="inboxCancelReject">Batal</button>
                    <button type="button" class="btn btn-danger" id="inboxSubmitReject">Kirim Reject</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
<style>
    .inbox-photo {
        width: 64px;
        height: 64px;
        border-radius: 6px;
        object-fit: cover;
    }
</style>
@endpush

@push('script')
<script>
    $(function () {
        let activeUrl = null;
        const csrfToken = @json(csrf_token());

        $('.inbox-decision').on('click', function () {
            activeUrl = $(this).data('url');
            $('#inboxItemName').text($(this).data('name') || 'Permohonan barang');
            $('#inboxComment').val('');
            $('#inboxCommentError, #inboxDecisionError').empty().addClass('d-none');
            $('#inboxRejectComment, #inboxRejectButtons').addClass('d-none');
            $('#inboxChoiceButtons').removeClass('d-none');
            $('#inboxDecisionModal').modal('show');
        });

        function sendDecision(decision, comment) {
            $.ajax({
                url: activeUrl,
                method: 'PATCH',
                data: { _token: csrfToken, decision: decision, comment: comment || '' },
                headers: { 'Accept': 'application/json' }
            }).done(function (response) {
                $('#inboxDecisionModal').modal('hide');
                $('#inboxMessage').html($('<div>').addClass('alert alert-success alert-dismissible fade show')
                    .append(document.createTextNode(response.message))
                    .append('<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>'));
                $('[id$="InboxTable"] tbody tr[data-id="' + response.data.id + '"]').remove();
                window.setTimeout(function () {
                    window.location.reload();
                }, 700);
            }).fail(function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.comment) {
                    $('#inboxCommentError').text(xhr.responseJSON.errors.comment[0]);
                    return;
                }
                const message = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'Keputusan approval gagal diproses.';
                $('#inboxDecisionError').removeClass('d-none').text(message);
            }).always(function () {
                $('#inboxApprove, #inboxSubmitReject').prop('disabled', false);
            });
        }

        $('#inboxApprove').on('click', function () {
            $(this).prop('disabled', true);
            sendDecision('approved', '');
        });

        $('#inboxReject').on('click', function () {
            $('#inboxChoiceButtons').addClass('d-none');
            $('#inboxRejectComment, #inboxRejectButtons').removeClass('d-none');
            $('#inboxComment').trigger('focus');
        });

        $('#inboxCancelReject').on('click', function () {
            $('#inboxRejectComment, #inboxRejectButtons').addClass('d-none');
            $('#inboxChoiceButtons').removeClass('d-none');
        });

        $('#inboxSubmitReject').on('click', function () {
            const comment = $('#inboxComment').val().trim();
            if (!comment) {
                $('#inboxCommentError').text('Komentar penolakan wajib diisi.');
                return;
            }
            $(this).prop('disabled', true);
            sendDecision('reject', comment);
        });
    });
</script>
@endpush
