<?php

namespace App\Http\Controllers;

use App\Models\Cctv;
use App\Services\MediaMtx;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class CctvController extends Controller
{
    public function index()
    {
        return view('pages.admin.cctv.index', [
            'cctvs' => Cctv::orderBy('nama_camera')->get(),
        ]);
    }

    public function data(): JsonResponse
    {
        return response()->json([
            'data' => Cctv::orderBy('nama_camera')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $cctv = Cctv::create($this->validatedData($request));

        return response()->json([
            'message' => 'Data CCTV berhasil ditambahkan.',
            'data' => $cctv,
        ], 201);
    }

    public function show(Cctv $cctv): JsonResponse
    {
        return response()->json(['data' => $cctv]);
    }

    public function playback(Cctv $cctv, MediaMtx $mediaMtx): JsonResponse
    {
        $streamUrl = $cctv->rtsp_url;

        if (preg_match('/^rtsps?:\/\//i', $streamUrl)) {
            try {
                $streamUrl = $mediaMtx->playbackUrl($cctv);
            } catch (RuntimeException $exception) {
                return response()->json(['message' => $exception->getMessage()], 503);
            }
        }

        $type = preg_match('/\.m3u8(?:[?#].*)?$/i', $streamUrl) ? 'hls' : 'iframe';

        return response()->json([
            'data' => [
                'url' => $streamUrl,
                'type' => $type,
            ],
        ]);
    }

    public function update(Request $request, Cctv $cctv): JsonResponse
    {
        $cctv->update($this->validatedData($request));

        return response()->json([
            'message' => 'Data CCTV berhasil diperbarui.',
            'data' => $cctv->refresh(),
        ]);
    }

    public function destroy(Cctv $cctv, MediaMtx $mediaMtx): JsonResponse
    {
        try {
            $mediaMtx->forget($cctv);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }

        $cctv->delete();

        return response()->json(['message' => 'Data CCTV berhasil dihapus.']);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'source_id' => ['nullable', 'integer', 'min:0'],
            'inventory_code' => ['nullable', 'string', 'max:255'],
            'nama_camera' => ['required', 'string', 'max:100'],
            'location' => ['required', 'string', 'max:100'],
            'ip_address' => ['required', 'ip'],
            'type_camera' => ['required', 'string', 'max:50'],
            'rtsp_url' => [
                'required',
                'string',
                'max:255',
                'regex:/^(rtsp|rtsps|https?):\/\/\S+$/i',
            ],
            'latitude' => ['required', 'string', 'max:200'],
            'longitude' => ['required', 'string', 'max:200'],
            'source_name' => ['nullable', 'string', 'max:255'],
            'sub_division' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'jenis_kategori' => ['nullable', 'string', 'max:255'],
            'merk' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'source_status' => ['nullable', 'string', 'max:50'],
            'reg_date' => ['nullable', 'date'],
            'nvr' => ['nullable', 'string', 'max:50'],
            'nomor_urut' => ['nullable', 'string', 'max:50'],
            'link_img' => ['nullable', 'string', 'max:255'],
            'source_model' => ['nullable', 'string', 'max:255'],
            'source_created_at' => ['nullable', 'date'],
            'source_updated_at' => ['nullable', 'date'],
        ]);
    }
}
