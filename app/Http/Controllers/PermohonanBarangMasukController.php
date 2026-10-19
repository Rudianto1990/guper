<?php

namespace App\Http\Controllers;

use App\Models\PermohonanBarangMasuk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PermohonanBarangMasukController extends Controller
{
    public function index()
    {
        $query = PermohonanBarangMasuk::query();
        $isAdmin = $this->isAdmin(request());

        if (! $isAdmin) {
            $query->where('user_created_by', request()->user()->getAuthIdentifier());
        }

        $permohonanBarangMasuks = $query->with('approvalLogs')->orderByDesc('id')->get();

        return view('pages.admin.permohonan-barang-masuk.index', compact('permohonanBarangMasuks', 'isAdmin'));
    }

    public function monitoring(Request $request)
    {
        abort_unless(
            in_array($request->user()->roles, ['admin', 'user', 'sepervisior'], true),
            403
        );

        $query = PermohonanBarangMasuk::with(['approvalLogs', 'creator', 'outgoingRequest']);
        if ($request->user()->roles === 'user') {
            $query->where('user_created_by', $request->user()->getAuthIdentifier());
        }

        $permohonanBarangMasuks = $query->orderByDesc('id')->get()
            ->each(function (PermohonanBarangMasuk $item) {
                $lastLog = $item->approvalLogs->last();
                $hasBeenSubmitted = $item->approvalLogs->contains('action', 'SUBMITTED');
                $isRejected = $lastLog?->action === 'REJECTED';
                $latestSubmission = $item->approvalLogs->where('action', 'SUBMITTED')->last();
                $stage1Log = $item->approvalLogs->where('approval_stage', 1)
                    ->whereIn('action', ['APPROVED', 'REJECTED'])->last();
                $stage2Log = $item->approvalLogs->where('approval_stage', 2)
                    ->whereIn('action', ['APPROVED', 'REJECTED'])->last();
                $stage1Current = $stage1Log && $latestSubmission && $stage1Log->id > $latestSubmission->id;
                $stage1Approved = $stage1Current && $stage1Log->action === 'APPROVED';
                $stage2Current = $stage2Log && $stage1Approved && $stage2Log->id > $stage1Log->id;
                $stage2Approved = $stage2Current && $stage2Log->action === 'APPROVED';
                $isApproved = $item->isFullyApproved();

                $item->setAttribute('monitoring_status', $isApproved
                    ? 'approved'
                    : ($isRejected ? 'rejected' : 'waiting'));
                $item->setAttribute('approval1_status', $stage1Approved
                    ? 'approved'
                    : ($stage1Current && $stage1Log->action === 'REJECTED' ? 'rejected' : 'waiting'));
                $item->setAttribute('approval2_status', $stage2Approved
                    ? 'approved'
                    : ($stage2Current && $stage2Log->action === 'REJECTED' ? 'rejected' : 'waiting'));
                $item->setAttribute('can_create_out', $isApproved);
                $item->setAttribute('has_outgoing_request', $item->outgoingRequest !== null);
            });

        $approvalCounts = [
            'all' => $permohonanBarangMasuks->count(),
            'waiting' => $permohonanBarangMasuks->where('monitoring_status', 'waiting')->count(),
            'approved' => $permohonanBarangMasuks->where('monitoring_status', 'approved')->count(),
            'rejected' => $permohonanBarangMasuks->where('monitoring_status', 'rejected')->count(),
        ];

        return view(
            'pages.admin.monitoring-approval.index',
            compact('permohonanBarangMasuks', 'approvalCounts')
        );
    }

    public function data(Request $request): JsonResponse
    {
        $query = PermohonanBarangMasuk::query();
        $isAdmin = $this->isAdmin($request);

        if (! $isAdmin) {
            $query->where('user_created_by', $request->user()->getAuthIdentifier());
        }

        return response()->json([
            'data' => $query->with('approvalLogs')->orderByDesc('id')->get()
                ->map(fn (PermohonanBarangMasuk $item) => $this->present($item, $request)),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $action = $this->validatedAction($request);
        $data = $this->validatedData($request, $action === 'submit');
        $bast = $request->file('bast')?->store('permohonan-barang-masuk/bast', 'public');
        $foto = $request->file('foto')?->store('permohonan-barang-masuk/foto', 'public');

        $item = DB::transaction(function () use ($data, $bast, $foto, $request, $action) {
            $data['kd_barang'] = 'TMP-'.Str::upper(Str::random(10));
            $data['bast'] = $bast;
            $data['foto'] = $foto;
            $data['user_created_by'] = $request->user()->getAuthIdentifier();
            $data['status_approved'] = $action === 'submit' ? 'APPROVED_1' : 'DRAFT';

            $item = PermohonanBarangMasuk::create($data);
            $item->kd_barang = 'ME-'.str_pad((string) $item->id, 5, '0', STR_PAD_LEFT);
            $item->save();

            if ($action === 'submit') {
                $item->approvalLogs()->create([
                    'action' => 'SUBMITTED',
                    'approval_stage' => 1,
                    'user_id' => $request->user()->getAuthIdentifier(),
                ]);
            }

            return $item;
        });

        $item->load('approvalLogs');

        return response()->json([
            'message' => $this->savedMessage($action, true),
            'data' => $this->present($item, $request),
        ], 201);
    }

    public function show(Request $request, PermohonanBarangMasuk $permohonanBarangMasuk): JsonResponse
    {
        abort_unless(
            $this->isAdmin($request)
                || $request->user()->roles === 'sepervisior'
                || $this->owns($permohonanBarangMasuk, $request),
            403
        );

        return response()->json(['data' => $this->present($permohonanBarangMasuk, $request)]);
    }

    public function attachment(
        Request $request,
        PermohonanBarangMasuk $permohonanBarangMasuk,
        string $type
    ) {
        abort_unless(in_array($type, ['bast', 'foto'], true), 404);
        abort_unless(
            $this->isAdmin($request)
                || $request->user()->roles === 'sepervisior'
                || $this->owns($permohonanBarangMasuk, $request),
            403
        );

        $path = $permohonanBarangMasuk->{$type};
        abort_unless($path && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response(
            $path,
            basename($path),
            ['Cache-Control' => 'private, no-store'],
            'inline'
        );
    }

    public function update(Request $request, PermohonanBarangMasuk $permohonanBarangMasuk): JsonResponse
    {
        abort_unless(
            $this->owns($permohonanBarangMasuk, $request) && $permohonanBarangMasuk->status_approved === 'DRAFT',
            403
        );

        $action = $this->validatedAction($request);
        $data = $this->validatedData($request, $action === 'submit', $permohonanBarangMasuk);
        $oldBast = $permohonanBarangMasuk->bast;
        $oldFoto = $permohonanBarangMasuk->foto;

        if ($request->hasFile('bast')) {
            $data['bast'] = $request->file('bast')->store('permohonan-barang-masuk/bast', 'public');
        } else {
            unset($data['bast']);
        }

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('permohonan-barang-masuk/foto', 'public');
        } else {
            unset($data['foto']);
        }

        $data['status_approved'] = $action === 'submit' ? 'APPROVED_1' : 'DRAFT';

        DB::transaction(function () use ($data, $action, $permohonanBarangMasuk, $request) {
            $permohonanBarangMasuk->update($data);

            if ($action === 'submit') {
                $permohonanBarangMasuk->approvalLogs()->create([
                    'action' => 'SUBMITTED',
                    'approval_stage' => 1,
                    'user_id' => $request->user()->getAuthIdentifier(),
                ]);
            }
        });

        if (isset($data['bast']) && $oldBast) {
            Storage::disk('public')->delete($oldBast);
        }

        if (isset($data['foto']) && $oldFoto) {
            Storage::disk('public')->delete($oldFoto);
        }

        return response()->json([
            'message' => $this->savedMessage($action, false),
            'data' => $this->present($permohonanBarangMasuk->refresh()->load('approvalLogs'), $request),
        ]);
    }

    public function approve(
        Request $request,
        PermohonanBarangMasuk $permohonanBarangMasuk,
        int $stage
    ): JsonResponse
    {
        abort_unless(in_array($stage, [1, 2], true), 404);
        abort_unless(
            ($stage === 1 && $this->isAdmin($request))
            || ($stage === 2 && $request->user()->roles === 'sepervisior'),
            403
        );
        abort_unless(
            $this->isPendingApprovalStage($permohonanBarangMasuk, $stage),
            422,
            'Permohonan ini tidak sedang menunggu persetujuan Anda.'
        );

        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'reject'])],
            'comment' => ['required_if:decision,reject', 'nullable', 'string', 'max:5000'],
        ]);

        if ($data['decision'] === 'approved' && $stage === 1) {
            abort_unless($this->isComplete($permohonanBarangMasuk), 422, 'Data atau dokumen wajib belum lengkap.');
        }

        $approved = $data['decision'] === 'approved';
        DB::transaction(function () use ($approved, $data, $permohonanBarangMasuk, $request, $stage) {
            $permohonanBarangMasuk->update([
                'status_approved' => $approved ? 'APPROVED_2' : 'DRAFT',
            ]);

            return $permohonanBarangMasuk->approvalLogs()->create([
                'approval_stage' => $stage,
                'action' => $approved ? 'APPROVED' : 'REJECTED',
                'comment' => $approved ? null : $data['comment'],
                'user_id' => $request->user()->getAuthIdentifier(),
            ]);
        });
        $permohonanBarangMasuk->load('approvalLogs');

        return response()->json([
            'message' => $approved
                ? ($stage === 1
                    ? 'Permohonan barang masuk disetujui admin dan diteruskan ke supervisor.'
                    : 'Permohonan barang masuk disetujui supervisor.')
                : 'Permohonan ditolak dan dikembalikan ke pemohon untuk diperbaiki.',
            'data' => $this->present($permohonanBarangMasuk->refresh()->load('approvalLogs'), $request),
        ]);
    }

    public function destroy(Request $request, PermohonanBarangMasuk $permohonanBarangMasuk): JsonResponse
    {
        abort_if(
            $permohonanBarangMasuk->outgoingRequest()->exists(),
            409,
            'Permohonan barang masuk tidak dapat dihapus karena sudah memiliki permohonan pengeluaran.'
        );

        abort_unless(
            $this->isAdmin($request) || (
                $this->owns($permohonanBarangMasuk, $request)
                && $permohonanBarangMasuk->status_approved === 'DRAFT'
            ),
            403
        );

        $bast = $permohonanBarangMasuk->bast;
        $foto = $permohonanBarangMasuk->foto;
        $permohonanBarangMasuk->delete();

        if ($bast) {
            Storage::disk('public')->delete($bast);
        }

        if ($foto) {
            Storage::disk('public')->delete($foto);
        }

        return response()->json(['message' => 'Permohonan barang masuk berhasil dihapus.']);
    }

    private function validatedAction(Request $request): string
    {
        return $request->validate([
            'submit_action' => ['required', Rule::in(['draft', 'submit'])],
        ])['submit_action'];
    }

    private function validatedData(
        Request $request,
        bool $requiresApproval,
        ?PermohonanBarangMasuk $existing = null
    ): array {
        $required = $requiresApproval ? 'required' : 'nullable';
        $bastRequired = $requiresApproval && ! ($existing?->bast) ? 'required' : 'nullable';
        $fotoRequired = $requiresApproval && ! ($existing?->foto) ? 'required' : 'nullable';

        return $request->validate([
            'tgl_permohonan' => [$required, 'date'],
            'nm_barang' => [$required, 'string', 'max:255'],
            'nama_pic' => [$required, 'string', 'max:255'],
            'divisi' => [$required, 'string', 'max:255'],
            'lokasi_penyimpanan' => [$required, 'string', 'max:255'],
            'kondisi_barang' => [$required, Rule::in(['BAIK', 'RUSAK'])],
            'qty_barang' => [$required, 'integer', 'min:1'],
            'asal_barang' => [$required, 'string', 'max:255'],
            'bast' => [$bastRequired, 'file', 'mimes:pdf', 'max:10240'],
            'foto' => [$fotoRequired, 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
        ]);
    }

    private function present(PermohonanBarangMasuk $item, Request $request): array
    {
        return array_merge($item->toArray(), [
            'bast_url' => $item->bast
                ? route('permohonan-barang-masuk.attachment', [$item, 'bast'])
                : null,
            'foto_url' => $item->foto
                ? route('permohonan-barang-masuk.attachment', [$item, 'foto'])
                : null,
            'can_edit' => $this->owns($item, $request) && $item->status_approved === 'DRAFT',
            'can_delete' => $this->isAdmin($request) || (
                $this->owns($item, $request) && $item->status_approved === 'DRAFT'
            ),
            'can_approve' => $this->isAdmin($request)
                && $this->isPendingApprovalStage($item, 1),
            'can_approve_stage2' => $request->user()->roles === 'sepervisior'
                && $this->isPendingApprovalStage($item, 2),
            'created_at_human' => $item->created_at?->locale('id')->diffForHumans(),
            'approval_logs' => $item->approvalLogs->map(fn ($log) => [
                'action' => $log->action,
                'comment' => $log->comment,
                'created_at_human' => $log->created_at->locale('id')->diffForHumans(),
            ])->values(),
        ]);
    }

    private function isAdmin(Request $request): bool
    {
        return $request->user()->roles === 'admin';
    }

    private function owns(PermohonanBarangMasuk $item, Request $request): bool
    {
        return (string) $item->user_created_by === (string) $request->user()->getAuthIdentifier();
    }

    private function savedMessage(string $action, bool $created): string
    {
        if ($action === 'submit') {
            return 'Permohonan barang masuk berhasil diajukan untuk approval.';
        }

        return $created
            ? 'Permohonan barang masuk berhasil disimpan sebagai draft.'
            : 'Draft permohonan barang masuk berhasil diperbarui.';
    }

    private function isComplete(PermohonanBarangMasuk $item): bool
    {
        foreach ([
            'tgl_permohonan',
            'nm_barang',
            'nama_pic',
            'divisi',
            'lokasi_penyimpanan',
            'kondisi_barang',
            'qty_barang',
            'asal_barang',
            'bast',
            'foto',
        ] as $field) {
            if (blank($item->{$field})) {
                return false;
            }
        }

        return (int) $item->qty_barang > 0
            && Storage::disk('public')->exists($item->bast)
            && Storage::disk('public')->exists($item->foto);
    }

    private function isPendingApprovalStage(PermohonanBarangMasuk $item, int $stage): bool
    {
        if ($stage === 1) {
            return $item->status_approved === 'APPROVED_1';
        }

        return $item->isWaitingForSupervisorApproval();
    }
}
