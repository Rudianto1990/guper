<?php

namespace App\Http\Controllers;

use App\Models\PermohonanBarangMasuk;
use App\Models\PermohonanBarangOut;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PermohonanBarangOutController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(in_array($request->user()->roles, ['admin', 'user', 'sepervisior', 'petugas'], true), 403);

        $query = PermohonanBarangOut::with(['approvalLogs', 'permohonanBarangMasuk']);
        if ($request->user()->roles === 'user') {
            $query->whereHas('permohonanBarangMasuk', fn ($sourceQuery) => $sourceQuery
                ->where('user_created_by', $request->user()->getAuthIdentifier()));
        }
        $permohonanBarangOuts = $query->orderByDesc('id')->get();

        $source = null;
        if ($request->filled('source') && $request->user()->roles === 'admin') {
            $source = PermohonanBarangMasuk::with('approvalLogs')
                ->findOrFail($request->integer('source'));
            abort_unless($this->isFullyApproved($source), 422, 'Barang belum disetujui pada kedua tahap approval.');
        }

        return view('pages.admin.permohonan-barang-out.index', compact('permohonanBarangOuts', 'source'));
    }

    public function data(Request $request): JsonResponse
    {
        abort_unless(in_array($request->user()->roles, ['admin', 'user', 'sepervisior', 'petugas'], true), 403);

        $query = PermohonanBarangOut::with(['approvalLogs', 'permohonanBarangMasuk']);
        if ($request->user()->roles === 'user') {
            $query->whereHas('permohonanBarangMasuk', fn ($sourceQuery) => $sourceQuery
                ->where('user_created_by', $request->user()->getAuthIdentifier()));
        }

        return response()->json(['data' => $query->orderByDesc('id')->get()
            ->map(fn (PermohonanBarangOut $item) => $this->present($item))]);
    }

    public function lookup(Request $request, string $kd_barang): JsonResponse
    {
        abort_unless($request->user()->roles === 'admin', 403);

        $source = PermohonanBarangMasuk::with('approvalLogs')
            ->where('kd_barang', $kd_barang)
            ->firstOrFail();
        abort_unless($this->isFullyApproved($source), 422, 'Barang belum disetujui pada kedua tahap approval.');

        return response()->json(['data' => $this->presentSource($source)]);
    }

    public function nextCode(Request $request): JsonResponse
    {
        abort_unless($request->user()->roles === 'admin', 403);

        $nextId = (int) (PermohonanBarangOut::max('id') ?? 0) + 1;
        if (DB::connection()->getDriverName() === 'mysql') {
            $nextId = (int) (DB::table('information_schema.TABLES')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', (new PermohonanBarangOut)->getTable())
                ->value('AUTO_INCREMENT') ?? $nextId);
        }

        return response()->json([
            'data' => ['kd_barang_out' => 'OUT-'.str_pad((string) $nextId, 5, '0', STR_PAD_LEFT)],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->roles === 'admin', 403);
        $action = $this->validatedAction($request);
        $data = $request->validate([
            'kd_barang' => ['required', 'string', 'max:16'],
            'tgl_permohonan_out' => ['required', 'date'],
            'tujuan_barang' => ['required', 'string', 'max:255'],
            'qty_barang' => ['required', 'integer', 'min:1'],
            'foto' => [$action === 'submit' ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        $out = DB::transaction(function () use ($data, $request, $action) {
            $source = PermohonanBarangMasuk::with('approvalLogs')
                ->where('kd_barang', $data['kd_barang'])
                ->lockForUpdate()
                ->firstOrFail();
            abort_unless($this->isFullyApproved($source), 422, 'Barang belum disetujui pada kedua tahap approval.');
            $this->ensureAvailableStock($source, (int) $data['qty_barang']);

            $outData = $this->copySourceFields($source);
            $outData['kd_barang_out'] = 'TMP-'.Str::upper(Str::random(10));
            $outData['tgl_permohonan_out'] = $data['tgl_permohonan_out'];
            $outData['tujuan_barang'] = $data['tujuan_barang'];
            $outData['qty_barang'] = $data['qty_barang'];
            $outData['foto'] = $request->file('foto')?->store('permohonan-barang-out/foto', 'public');
            $outData['status_approved'] = $action === 'submit' ? 'APPROVED_1' : 'DRAFT';
            $outData['user_created_by'] = $request->user()->getAuthIdentifier();
            $outData['permohonan_barang_masuk_id'] = $source->id;

            $out = PermohonanBarangOut::create($outData);
            $out->kd_barang_out = 'OUT-'.str_pad((string) $out->id, 5, '0', STR_PAD_LEFT);
            $out->save();
            if ($action === 'submit') {
                $this->recordSubmission($out, $request);
            }

            return $out;
        });

        return response()->json([
            'message' => $action === 'submit'
                ? 'Permohonan pengeluaran barang berhasil diajukan ke supervisor.'
                : 'Draft permohonan pengeluaran barang berhasil disimpan.',
            'data' => $this->present($out->load(['approvalLogs', 'permohonanBarangMasuk'])),
        ], 201);
    }

    public function show(Request $request, PermohonanBarangOut $permohonanBarangOut): JsonResponse
    {
        abort_unless(
            in_array($request->user()->roles, ['admin', 'sepervisior', 'petugas'], true)
                || $this->belongsToUser($permohonanBarangOut, $request),
            403
        );

        return response()->json([
            'data' => $this->present($permohonanBarangOut->load(['approvalLogs', 'permohonanBarangMasuk'])),
        ]);
    }

    public function update(Request $request, PermohonanBarangOut $permohonanBarangOut): JsonResponse
    {
        abort_unless($request->user()->roles === 'admin', 403);
        abort_unless($permohonanBarangOut->isDraftOrRejected(), 403, 'Hanya draft atau permohonan yang ditolak dapat diperbarui.');

        $action = $this->validatedAction($request);
        $data = $request->validate([
            'kd_barang' => ['required', 'string', 'max:16'],
            'tgl_permohonan_out' => ['required', 'date'],
            'tujuan_barang' => ['required', 'string', 'max:255'],
            'qty_barang' => ['required', 'integer', 'min:1'],
            'foto' => [
                $action === 'submit' && ! $permohonanBarangOut->foto ? 'required' : 'nullable',
                'image',
                'mimes:jpg,jpeg,png',
                'max:5120',
            ],
        ]);
        $oldPhoto = $permohonanBarangOut->foto;

        DB::transaction(function () use ($data, $action, $permohonanBarangOut, $request) {
            $source = PermohonanBarangMasuk::with('approvalLogs')
                ->where('kd_barang', $data['kd_barang'])
                ->lockForUpdate()
                ->firstOrFail();
            abort_unless($this->isFullyApproved($source), 422, 'Barang belum disetujui pada kedua tahap approval.');
            $this->ensureAvailableStock($source, (int) $data['qty_barang'], $permohonanBarangOut->id);

            $update = array_merge(
                $this->copySourceFields($source),
                [
                    'tgl_permohonan_out' => $data['tgl_permohonan_out'],
                    'tujuan_barang' => $data['tujuan_barang'],
                    'qty_barang' => $data['qty_barang'],
                    'permohonan_barang_masuk_id' => $source->id,
                    'status_approved' => $action === 'submit' ? 'APPROVED_1' : 'DRAFT',
                ]
            );
            if ($request->hasFile('foto')) {
                $update['foto'] = $request->file('foto')->store('permohonan-barang-out/foto', 'public');
            }

            $permohonanBarangOut->update($update);
            if ($action === 'submit') {
                $this->recordSubmission($permohonanBarangOut, $request);
            }
        });

        if ($request->hasFile('foto') && $oldPhoto) {
            Storage::disk('public')->delete($oldPhoto);
        }

        return response()->json([
            'message' => $action === 'submit'
                ? 'Permohonan pengeluaran barang diperbarui dan diajukan ke supervisor.'
                : 'Draft permohonan pengeluaran barang berhasil diperbarui.',
            'data' => $this->present($permohonanBarangOut->refresh()->load(['approvalLogs', 'permohonanBarangMasuk'])),
        ]);
    }

    public function decide(Request $request, PermohonanBarangOut $permohonanBarangOut): JsonResponse
    {
        $role = $request->user()->roles;
        $stage = $role === 'sepervisior' ? 3 : 4;
        abort_unless(in_array($role, ['sepervisior', 'petugas'], true), 403);

        $permohonanBarangOut->load('approvalLogs');
        abort_unless(
            ($stage === 3 && $permohonanBarangOut->isWaitingForSupervisorApproval())
            || ($stage === 4 && $permohonanBarangOut->isWaitingForPetugasApproval()),
            422,
            'Permohonan ini tidak sedang menunggu approval Anda.'
        );

        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'reject'])],
            'comment' => ['required_if:decision,reject', 'nullable', 'string', 'max:5000'],
        ]);
        $approved = $data['decision'] === 'approved';

        DB::transaction(function () use ($stage, $approved, $data, $permohonanBarangOut, $request) {
            $permohonanBarangOut->update([
                'status_approved' => $approved ? 'APPROVED_2' : 'DRAFT',
            ]);
            $permohonanBarangOut->approvalLogs()->create([
                'approval_stage' => $stage,
                'action' => $approved ? 'APPROVED' : 'REJECTED',
                'comment' => $approved ? null : $data['comment'],
                'user_id' => $request->user()->getAuthIdentifier(),
            ]);
            if ($approved && $stage === 3) {
                $permohonanBarangOut->approvalLogs()->create([
                    'approval_stage' => 4,
                    'action' => 'REQUESTED',
                    'user_id' => $request->user()->getAuthIdentifier(),
                ]);
            }
        });

        return response()->json([
            'message' => $approved
                ? ($stage === 3
                    ? 'Permohonan pengeluaran disetujui supervisor dan diteruskan ke petugas.'
                    : 'Permohonan pengeluaran disetujui petugas.')
                : 'Permohonan pengeluaran ditolak dan dikembalikan ke admin.',
            'data' => $this->present($permohonanBarangOut->refresh()->load(['approvalLogs', 'permohonanBarangMasuk'])),
        ]);
    }

    public function attachment(Request $request, PermohonanBarangOut $permohonanBarangOut, string $type)
    {
        abort_unless(in_array($type, ['bast', 'foto'], true), 404);
        abort_unless(
            in_array($request->user()->roles, ['admin', 'petugas', 'sepervisior'], true)
                || $this->belongsToUser($permohonanBarangOut, $request),
            403
        );

        $path = $permohonanBarangOut->{$type};
        abort_unless($path && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, basename($path), [
            'Cache-Control' => 'private, no-store',
        ], 'inline');
    }

    public function destroy(Request $request, PermohonanBarangOut $permohonanBarangOut): JsonResponse
    {
        abort_unless($request->user()->roles === 'admin', 403);
        $permohonanBarangOut->load('approvalLogs');
        abort_unless($permohonanBarangOut->isDraftOrRejected(), 403);

        if ($permohonanBarangOut->foto) {
            Storage::disk('public')->delete($permohonanBarangOut->foto);
        }
        $permohonanBarangOut->delete();

        return response()->json(['message' => 'Permohonan pengeluaran barang berhasil dihapus.']);
    }

    private function isFullyApproved(PermohonanBarangMasuk $source): bool
    {
        return $source->isFullyApproved();
    }

    private function belongsToUser(PermohonanBarangOut $item, Request $request): bool
    {
        return $request->user()->roles === 'user'
            && (string) $item->permohonanBarangMasuk()->value('user_created_by')
                === (string) $request->user()->getAuthIdentifier();
    }

    private function present(PermohonanBarangOut $item): array
    {
        $logs = $item->approvalLogs;
        $lastLog = $logs->last();
        $source = $item->permohonanBarangMasuk;
        $sourceStock = (int) ($source?->qty_barang ?? 0);
        $otherReserved = $source ? $this->reservedStock($source->id, $item->id) : 0;
        $thisRequestIsReserved = $lastLog?->action !== 'REJECTED';

        return array_merge($item->toArray(), [
            'created_at_human' => $item->created_at?->locale('id')->diffForHumans(),
            'stock_tersedia' => max(
                0,
                $sourceStock - $otherReserved + ($thisRequestIsReserved ? (int) $item->qty_barang : 0)
            ),
            'foto_url' => $item->foto
                ? route('permohonan-barang-out.attachment', [$item, 'foto'])
                : null,
            'bast_url' => $item->bast
                ? route('permohonan-barang-out.attachment', [$item, 'bast'])
                : null,
            'can_edit' => $item->isDraftOrRejected(),
            'can_delete' => $item->isDraftOrRejected(),
            'is_processing' => in_array($lastLog?->action, ['REQUESTED', 'APPROVED'], true)
                && $item->status_approved !== 'DRAFT',
            'approval_logs' => $logs->map(fn ($log) => [
                'action' => $log->action,
                'approval_stage' => $log->approval_stage,
                'comment' => $log->comment,
                'created_at_human' => $log->created_at->locale('id')->diffForHumans(),
            ])->values(),
        ]);
    }

    private function validatedAction(Request $request): string
    {
        return $request->validate([
            'submit_action' => ['required', Rule::in(['draft', 'submit'])],
        ])['submit_action'];
    }

    private function presentSource(PermohonanBarangMasuk $source): array
    {
        $reserved = $this->reservedStock($source->id);

        return [
            'id' => $source->id,
            'kd_barang' => $source->kd_barang,
            'nm_barang' => $source->nm_barang,
            'divisi' => $source->divisi,
            'nama_pic' => $source->nama_pic,
            'stock' => (int) $source->qty_barang,
            'stock_tersedia' => max(0, (int) $source->qty_barang - $reserved),
            'foto_url' => $source->foto
                ? route('permohonan-barang-masuk.attachment', [$source, 'foto'])
                : null,
        ];
    }

    private function copySourceFields(PermohonanBarangMasuk $source): array
    {
        return $source->only([
            'kd_barang',
            'tgl_permohonan',
            'nm_barang',
            'nama_pic',
            'divisi',
            'lokasi_penyimpanan',
            'kondisi_barang',
            'asal_barang',
            'keterangan',
            'attribute1',
            'attribute2',
            'attribute3',
            'attribute4',
        ]);
    }

    private function ensureAvailableStock(PermohonanBarangMasuk $source, int $quantity, ?int $exceptId = null): void
    {
        $available = max(0, (int) $source->qty_barang - $this->reservedStock($source->id, $exceptId));
        abort_if($quantity > $available, 422, 'Stock Barang Tidak Sesuai/Habis!!!');
    }

    private function reservedStock(int $sourceId, ?int $exceptId = null): int
    {
        return PermohonanBarangOut::with('approvalLogs')
            ->where('permohonan_barang_masuk_id', $sourceId)
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->get()
            ->filter(fn (PermohonanBarangOut $item) => $item->approvalLogs->last()?->action !== 'REJECTED')
            ->sum(fn (PermohonanBarangOut $item) => (int) $item->qty_barang);
    }

    private function recordSubmission(PermohonanBarangOut $item, Request $request): void
    {
        $item->approvalLogs()->create([
            'approval_stage' => 3,
            'action' => 'REQUESTED',
            'user_id' => $request->user()->getAuthIdentifier(),
        ]);
    }
}
