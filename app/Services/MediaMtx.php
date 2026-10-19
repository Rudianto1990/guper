<?php

namespace App\Services;

use App\Models\Cctv;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MediaMtx
{
    public function forget(Cctv $cctv): void
    {
        $apiUrl = rtrim((string) config('services.mediamtx.api_url'), '/');
        if ($apiUrl === '') {
            return;
        }

        $path = rawurlencode('cctv-'.$cctv->id);

        try {
            $response = $this->request()->delete($apiUrl.'/v3/config/paths/delete/'.$path);
        } catch (ConnectionException $exception) {
            Log::warning('MediaMTX API is unavailable while deleting a CCTV path.', [
                'cctv_id' => $cctv->id,
            ]);

            throw new RuntimeException('Gateway MediaMTX tidak dapat dihubungi. Data CCTV belum dihapus.', 0, $exception);
        }

        if (! $response->successful() && $response->status() !== 404) {
            Log::warning('MediaMTX rejected a CCTV path deletion.', [
                'cctv_id' => $cctv->id,
                'status' => $response->status(),
            ]);

            throw new RuntimeException('Gateway MediaMTX gagal membersihkan stream kamera. Data CCTV belum dihapus.');
        }
    }

    public function playbackUrl(Cctv $cctv): string
    {
        $apiUrl = rtrim((string) config('services.mediamtx.api_url'), '/');
        $hlsUrl = rtrim((string) config('services.mediamtx.hls_url'), '/');

        if ($apiUrl === '' || $hlsUrl === '') {
            throw new RuntimeException('MediaMTX belum dikonfigurasi. Isi MEDIAMTX_API_URL dan MEDIAMTX_HLS_URL pada file .env.');
        }

        $path = 'cctv-'.$cctv->id;
        $encodedPath = rawurlencode($path);
        $pathsEndpoint = $apiUrl.'/v3/config/paths';
        $payload = [
            'source' => $cctv->rtsp_url,
            'sourceOnDemand' => true,
        ];

        try {
            $response = $this->request()->patch($pathsEndpoint.'/patch/'.$encodedPath, $payload);

            if ($response->status() === 404) {
                $response = $this->request()->post($pathsEndpoint.'/add/'.$encodedPath, $payload);
            }
        } catch (ConnectionException $exception) {
            Log::warning('MediaMTX API is unavailable while opening a CCTV stream.', [
                'cctv_id' => $cctv->id,
            ]);

            throw new RuntimeException('Gateway MediaMTX tidak dapat dihubungi. Pastikan servicenya berjalan dan konfigurasi URL benar.', 0, $exception);
        }

        if (! $response->successful()) {
            Log::warning('MediaMTX rejected a CCTV path configuration.', [
                'cctv_id' => $cctv->id,
                'status' => $response->status(),
            ]);

            throw new RuntimeException('Gateway MediaMTX gagal menyiapkan stream kamera. Periksa konfigurasi gateway dan URL RTSP kamera.');
        }

        return $hlsUrl.'/'.$path.'/index.m3u8';
    }

    private function request(): PendingRequest
    {
        $request = Http::acceptJson()->asJson()->timeout(5);
        $username = config('services.mediamtx.api_username');
        $password = config('services.mediamtx.api_password');

        if ($username !== null && $username !== '' && $password !== null && $password !== '') {
            $request = $request->withBasicAuth($username, $password);
        }

        return $request;
    }
}
