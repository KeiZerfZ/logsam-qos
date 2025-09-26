<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QoS Logging Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Ikon dari Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
</head>
<body class="bg-gray-100 text-gray-800">

    <div class="container mx-auto p-8">
        <h1 class="text-4xl font-bold mb-8 text-center text-gray-700">QoS Logging Dashboard</h1>

        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative text-center mb-4" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <div class="bg-white shadow-md rounded-lg p-6 mb-8">
            <h2 class="text-2xl font-bold mb-4 text-gray-700 text-center">Manual Data Entry</h2>
            <p class="text-center text-gray-500 mb-6">Gunakan aplikasi Ookla Speedtest di lokasi, lalu masukkan hasilnya di sini.</p>
            <form action="{{ route('loggings.store') }}" method="POST" class="max-w-xl mx-auto">
                @csrf
                <div class="mb-4">
                    <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Nama Tes / Lokasi:</label>
                    <input type="text" name="name" id="name" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <label for="delay" class="block text-gray-700 text-sm font-bold mb-2">Ping / Delay (ms):</label>
                        <input type="number" step="any" name="delay" id="delay" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div>
                        <label for="jitter" class="block text-gray-700 text-sm font-bold mb-2">Jitter (ms):</label>
                        <input type="number" step="any" name="jitter" id="jitter" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div>
                        <label for="loss" class="block text-gray-700 text-sm font-bold mb-2">Packet Loss (%):</label>
                        <input type="number" step="any" name="loss" id="loss" required value="0" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                </div>
                <div class="text-center">
                    <button type="submit" class="bg-green-600 text-white font-bold py-2 px-6 rounded-lg hover:bg-green-700 transition duration-300 shadow-lg">
                        <i class="fas fa-save mr-2"></i> Simpan Data Manual
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <table class="min-w-full leading-normal">
                <thead>
                    <tr class="bg-gray-200 text-left text-gray-600 uppercase text-sm">
                        <th class="py-3 px-5">ID</th>
                        <th class="py-3 px-5">Name</th>
                        <th class="py-3 px-5">Timestamp</th>
                        <th class="py-3 px-5 text-center">Delay (ms)</th>
                        <th class="py-3 px-5 text-center">Jitter (ms)</th>
                        <th class="py-3 px-5 text-center">Loss (%)</th>
                        <th class="py-3 px-5 text-center font-bold">QoS Score</th>
                        <th class="py-3 px-5 text-center">Category</th>
                        <th class="py-3 px-5 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    @forelse ($logs as $log)
                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="py-3 px-5">{{ $log->id }}</td>
                            <td class="py-3 px-5">{{ $log->name }}</td>
                            <td class="py-3 px-5">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                            <td class="py-3 px-5 text-center">{{ $log->delay_median_ms }}</td>
                            <td class="py-3 px-5 text-center">{{ round($log->jitter_avg_ms, 3) }}</td>
                            <td class="py-3 px-5 text-center">{{ $log->loss_pct }}</td>
                            <td class="py-3 px-5 text-center font-bold text-indigo-600">{{ $log->score_qos }}</td>
                            <td class="py-3 px-5 text-center">
                                <span class="relative inline-block px-3 py-1 font-semibold leading-tight
                                    @if($log->category_qos == 'Sangat Baik') text-green-900 @elseif($log->category_qos == 'Baik') text-blue-900 @elseif($log->category_qos == 'Cukup') text-yellow-900 @else text-red-900 @endif">
                                    <span aria-hidden class="absolute inset-0 
                                        @if($log->category_qos == 'Sangat Baik') bg-green-200 @elseif($log->category_qos == 'Baik') bg-blue-200 @elseif($log->category_qos == 'Cukup') bg-yellow-200 @else bg-red-200 @endif
                                        opacity-50 rounded-full"></span>
                                    <span class="relative">{{ $log->category_qos }}</span>
                                </span>
                            </td>
                            <td class="py-3 px-5 text-center flex justify-center items-center space-x-4">
                                <button data-url="{{ route('loggings.detail', $log) }}" class="text-blue-500 hover:text-blue-700 font-semibold detail-btn">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <form action="{{ route('loggings.destroy', $log) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 font-semibold">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-10 text-gray-500">
                                Belum ada data logging.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-8 bg-white shadow-md rounded-lg p-6">
           <h2 class="text-2xl font-bold mb-4 text-gray-700">QoS Score History</h2>
           <canvas id="qosChart"></canvas>
        </div>
    </div>

    {{-- MODAL POPUP (SECARA DEFAULT TERSEMBUNYI) --}}
    <div id="detail-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto p-6 relative">
            <button id="close-modal-btn" class="absolute top-4 right-4 text-gray-500 hover:text-gray-800 text-2xl leading-none">&times;</button>
            <h2 class="text-2xl font-bold mb-4">Detail Log #<span id="modal-log-id"></span></h2>
            <div id="modal-content" class="text-center">
                <p>Loading...</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script type="module">
        // --- Chart.js ---
        const logs = @json($logs->sortBy('id')->values());
        const labels = logs.map(log => `Test #${log.id} (${new Date(log.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })})`);
        const qosScores = logs.map(log => log.score_qos);
        const ctx = document.getElementById('qosChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'QoS Score',
                    data: qosScores,
                    backgroundColor: 'rgba(79, 70, 229, 0.2)',
                    borderColor: 'rgba(79, 70, 229, 1)',
                    borderWidth: 3,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: { scales: { y: { beginAtZero: true, max: 100 } }, plugins: { legend: { display: false } } }
        });

        // --- Modal Logic ---
        const modal = document.getElementById('detail-modal');
        const closeModalBtn = document.getElementById('close-modal-btn');
        const modalContent = document.getElementById('modal-content');
        const modalLogId = document.getElementById('modal-log-id');
        const detailButtons = document.querySelectorAll('.detail-btn');

        detailButtons.forEach(button => {
            button.addEventListener('click', () => {
                const url = button.dataset.url;
                modal.classList.remove('hidden');
                modalContent.innerHTML = '<p class="py-16">Generating graph, please wait...</p>';

                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) throw new Error(data.error);
                        
                        modalLogId.textContent = data.log.id;
                        
                        const contentHtml = `
                            <div class="mb-6 border-b pb-4">
                                <h3 class="text-lg font-semibold">Metrics</h3>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-2 text-sm">
                                    <div><span class="font-bold">Name:</span> ${data.log.name}</div>
                                    <div><span class="font-bold">Score:</span> ${data.log.score_qos}</div>
                                    <div><span class="font-bold">Category:</span> ${data.log.category_qos}</div>
                                    <div><span class="font-bold">Timestamp:</span> ${new Date(data.log.created_at).toLocaleString('id-ID')}</div>
                                </div>
                            </div>
                            <h3 class="text-lg font-semibold mb-2">Fuzzy Process Visualization</h3>
                            <img src="${data.graph_image}" alt="Fuzzy Process Graph" class="mx-auto border rounded-md"/>
                        `;
                        modalContent.innerHTML = contentHtml;
                    })
                    .catch(error => {
                        console.error('Error fetching detail:', error);
                        modalContent.innerHTML = `<p class="text-red-500 font-bold">Failed to generate graph.</p><pre class="mt-4 text-xs text-left bg-gray-100 p-2 rounded whitespace-pre-wrap">${error.message}</pre>`;
                    });
            });
        });

        closeModalBtn.addEventListener('click', () => {
            modal.classList.add('hidden');
        });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.classList.add('hidden');
            }
        });
    </script>
</body>
</html>