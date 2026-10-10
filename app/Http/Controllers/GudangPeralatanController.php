<?php

namespace App\Http\Controllers;

use App\Models\GudangPeralatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class GudangPeralatanController extends Controller
{
    public function index()
    {
        $gudangPeralatans = GudangPeralatan::orderBy('nama_barang')->get();

        return view('pages.admin.gudang-peralatan.index', compact('gudangPeralatans'));
    }

    public function create()
    {
        return view('pages.admin.gudang-peralatan.create');
    }

    public function store(Request $request)
    {
        $request->merge(['id_barang' => $this->generateItemId()]);
        $data = $this->validatedData($request);

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('gudang-peralatan', 'public');
        }

        GudangPeralatan::create($data);

        return redirect()->route('gudang-peralatan.index')
            ->with('success', 'Data peralatan berhasil ditambahkan.');
    }

    public function show(GudangPeralatan $gudangPeralatan)
    {
        return redirect()->route('gudang-peralatan.index');
    }

    public function cctv(GudangPeralatan $gudangPeralatan)
    {
        abort_unless($gudangPeralatan->url_cctv, 404);

        $url = $gudangPeralatan->url_cctv;
        $parts = parse_url($url);
        abort_unless($parts && isset($parts['host']), 422, 'URL CCTV tidak valid.');

        $username = isset($parts['user']) ? rawurldecode($parts['user']) : null;
        $password = isset($parts['pass']) ? rawurldecode($parts['pass']) : null;
        $remoteUrl = preg_replace('/^(https?:\/\/)[^\/]+@/i', '$1', $url);
        $request = Http::connectTimeout(5)->timeout(15);

        if ($username !== null) {
            $request = $request->withOptions([
                'auth' => [$username, $password ?? '', 'digest'],
            ]);
        }

        $response = $request->get($remoteUrl);
        abort_unless($response->successful(), 502, 'CCTV tidak dapat diakses.');

        return response($response->body(), 200, [
            'Content-Type' => $response->header('Content-Type', 'image/jpeg'),
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function edit(GudangPeralatan $gudangPeralatan)
    {
        return view('pages.admin.gudang-peralatan.edit', compact('gudangPeralatan'));
    }

    public function update(Request $request, GudangPeralatan $gudangPeralatan)
    {
        $data = $this->validatedData($request, $gudangPeralatan);

        if ($request->hasFile('foto')) {
            if ($gudangPeralatan->foto) {
                Storage::disk('public')->delete($gudangPeralatan->foto);
            }

            $data['foto'] = $request->file('foto')->store('gudang-peralatan', 'public');
        }

        $gudangPeralatan->update($data);

        return redirect()->route('gudang-peralatan.index')
            ->with('success', 'Data peralatan berhasil diperbarui.');
    }

    public function destroy(GudangPeralatan $gudangPeralatan)
    {
        if ($gudangPeralatan->foto) {
            Storage::disk('public')->delete($gudangPeralatan->foto);
        }

        $gudangPeralatan->delete();

        return redirect()->route('gudang-peralatan.index')
            ->with('success', 'Data peralatan berhasil dihapus.');
    }

    private function validatedData(Request $request, ?GudangPeralatan $gudangPeralatan = null): array
    {
        if ($gudangPeralatan) {
            $request->merge(['id_barang' => $gudangPeralatan->id_barang]);
        }

        $request->merge([
            'url_cctv' => $request->filled('url_cctv')
                ? trim((string) $request->input('url_cctv'))
                : null,
        ]);

        return $request->validate([
            'id_barang' => [
                'required',
                'string',
                'max:100',
                Rule::unique('gudang_peralatans', 'id_barang')->ignore($gudangPeralatan?->id),
            ],
            'nama_barang' => ['required', 'string', 'max:255'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'qty' => ['required', 'integer', 'min:0'],
            'tahun' => ['nullable', 'integer', 'min:1900', 'max:' . ((int) date('Y') + 1)],
            'satuan' => ['required', 'string', 'max:50'],
            'lokasi' => ['required', 'string', 'max:255'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'gudang_area' => ['required', 'string', 'max:255'],
            'kondisi' => ['required', Rule::in(['BAIK', 'RUSAK'])],
            'mekanisme_penghapusan' => ['nullable', 'string', 'max:5000'],
            'alasan_hapus' => ['nullable', 'string', 'max:5000'],
            'tata_cara' => ['nullable', 'string', 'max:5000'],
            'url_cctv' => ['nullable', 'url', 'max:2048'],
        ]);
    }

    private function generateItemId(): string
    {
        do {
            $idBarang = 'BRG-' . strtoupper(Str::random(8));
        } while (GudangPeralatan::where('id_barang', $idBarang)->exists());

        return $idBarang;
    }
}