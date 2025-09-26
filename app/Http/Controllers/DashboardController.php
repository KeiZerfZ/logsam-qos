<?php

namespace App\Http\Controllers;

use App\Models\Logging;
use App\Services\FuzzyQosService;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process; // Penting untuk memanggil script eksternal

class DashboardController extends Controller
{
    /**
     * Menampilkan halaman dashboard dengan semua data logging.
     */
    public function index()
    {
        $logs = Logging::latest()->get();
        return view('dashboard', ['logs' => $logs]);
    }

    /**
     * Menyimpan data baru dari form input manual.
     */
    public function store(Request $request, FuzzyQosService $qosService)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'delay' => 'required|numeric|min:0',
            'jitter' => 'required|numeric|min:0',
            'loss' => 'required|numeric|min:0|max:100',
        ]);

        $fuzzyResult = $qosService->calculate([
            'delay' => $validatedData['delay'],
            'jitter' => $validatedData['jitter'],
            'loss' => $validatedData['loss'],
        ]);

        $dataToSave = [
            'name' => $validatedData['name'],
            'delay_median_ms' => $validatedData['delay'],
            'delay_p95_ms' => $validatedData['delay'],
            'jitter_avg_ms' => $validatedData['jitter'],
            'loss_pct' => $validatedData['loss'],
            'bitrate_sent_mbps' => 0,
            'score_qos' => $fuzzyResult['score_qos'],
            'category_qos' => $fuzzyResult['category_qos'],
        ];

        Logging::create($dataToSave);

        return back()->with('success', 'Data log manual berhasil ditambahkan!');
    }

    /**
     * Menghapus data log.
     */
    public function destroy(Logging $log)
    {
        $log->delete();
        return back()->with('success', 'Data log berhasil dihapus!');
    }

    /**
     * Mengambil detail dan memanggil script Python untuk generate grafik.
     */
    public function showDetail(Logging $log)
    {
        // Untuk Laragon/Windows, path-nya biasanya sudah ada di ENV PATH, jadi cukup 'python'
        $pythonPath = 'python'; 
        $scriptPath = base_path('scripts/python/generate_fuzzy_plot.py');

        $process = new Process(
            [
                $pythonPath,
                $scriptPath,
                $log->delay_median_ms,
                $log->jitter_avg_ms,
                $log->loss_pct,
            ],
            null,
            [
                'PYTHONHASHSEED' => 0,
                // BERI TAHU MATPLOTLIB DI MANA HARUS MENYIMPAN CACHE-NYA
                'MPLCONFIGDIR' => storage_path('app/matplotlib_cache'),
            ]
        );

        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json(['error' => $process->getErrorOutput()], 500);
        }

        $base64Image = $process->getOutput();

        return response()->json([
            'log' => $log,
            'graph_image' => 'data:image/png;base64,' . trim($base64Image),
        ]);
    }
}