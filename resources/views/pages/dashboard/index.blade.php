@extends('layouts.main')
@section('title', 'Dashboard')

@section('content')
<section class="section custom-section dashboard-page" id="inventoryDashboard" data-url="{{ route('dashboard.data') }}">
    <div class="section-header">
        <h1>Dashboard</h1>
    </div>
    <div class="section-body">
        <div class="d-flex justify-content-end mb-3">
            <label class="sr-only" for="dashboardMonth">Periode dashboard</label>
            <select class="form-control form-control-sm dashboard-month" id="dashboardMonth" aria-label="Pilih periode dashboard">
                @foreach ($months as $value => $label)
                    <option value="{{ $value }}" @selected($value === $selectedMonth)>{{ $value === $selectedMonth ? 'Bulan Ini' : $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card card-statistic-2">
                    <div class="card-icon shadow-primary bg-primary">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header"><h4>Barang Tersimpan</h4></div>
                        <div class="card-body" id="storedUnits">-</div>
                    </div>
                    <div class="card-stats"><div class="card-stats-title">Unit di Gudang</div></div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-statistic-2">
                    <div class="card-icon shadow-info bg-info">
                        <i class="far fa-clock"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header"><h4>Mean Penyimpanan</h4></div>
                        <div class="card-body"><span id="meanStorageDays">-</span> Hari</div>
                    </div>
                    <div class="card-stats"><div class="card-stats-title">Rata-rata umur stok</div></div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-statistic-2">
                    <div class="card-icon shadow-success bg-success">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header"><h4>Barang Masuk</h4></div>
                        <div class="card-body" id="incomingUnits">-</div>
                    </div>
                    <div class="card-stats"><div class="card-stats-title">Unit pada <span data-period-label>bulan ini</span></div></div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-statistic-2">
                    <div class="card-icon shadow-warning bg-warning">
                        <i class="fas fa-dolly"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header"><h4>Barang Keluar</h4></div>
                        <div class="card-body" id="outgoingUnits">-</div>
                    </div>
                    <div class="card-stats"><div class="card-stats-title">Unit pada <span data-period-label>bulan ini</span></div></div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Statistik Barang Masuk dan Keluar</h4>
                        <div class="card-header-action text-muted small">Perbandingan unit per bulan</div>
                    </div>
                    <div class="card-body">
                        <div class="dashboard-chart-container">
                            <canvas id="movementChart" aria-label="Grafik barang masuk dan keluar"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Durasi Penyimpanan Barang</h4>
                        <div class="card-header-action text-muted small">Distribusi unit berdasarkan lama tersimpan</div>
                    </div>
                    <div class="card-body">
                        <div class="dashboard-chart-container">
                            <canvas id="storageChart" aria-label="Grafik durasi penyimpanan"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4>Permohonan Menunggu Approval</h4>
                <div class="card-header-action">
                    <span class="badge badge-info" id="pendingTotal">Memuat...</span>
                </div>
            </div>
            <div class="card-body p-0">
                <a class="pending-row" id="pendingIncomingLink" href="{{ route('approval-inbox.index') }}">
                    <span>Barang Masuk</span>
                    <strong id="pendingIncoming">-</strong>
                    <i class="fas fa-chevron-right"></i>
                </a>
                <a class="pending-row" id="pendingOutgoingLink" href="{{ route('permohonan-barang-out.index') }}">
                    <span>Barang Keluar</span>
                    <strong id="pendingOutgoing">-</strong>
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>
        <div class="alert alert-danger d-none" id="dashboardError" role="alert"></div>
    </div>
</section>
@endsection

@push('style')
<style>
    .dashboard-month { width: auto; min-width: 140px; border-radius: 20px; }
    .dashboard-chart-container { position: relative; height: 230px; }
    .card.card-statistic-2 .card-stats { clear: both; margin: 12px -20px -10px; padding: 10px 20px; border-top: 1px solid #f9f9f9; }
    .card.card-statistic-2 .card-stats-title { color: #98a6ad; font-size: 12px; }
    .card.card-statistic-2 .card-icon { width: 60px; height: 60px; margin: 20px; line-height: 60px; border-radius: 50%; text-align: center; }
    .card.card-statistic-2 .card-icon .fas, .card.card-statistic-2 .card-icon .far { color: #fff; font-size: 22px; }
    .card.card-statistic-2 .card-header { padding-top: 20px; }
    .pending-row { min-height: 36px; padding: 7px 20px; border-bottom: 1px solid #f2f2f2; display: flex; align-items: center; gap: 10px; color: #34395e; font-size: 13px; }
    .pending-row:last-child { border-bottom: 0; }
    .pending-row:hover { color: #2878cf; text-decoration: none; }
    .pending-row span { flex: 1; }
    .pending-row strong { color: #34395e; }
    .pending-row i { width: 12px; text-align: center; }
    @media (max-width: 575.98px) {
        .dashboard-chart-container { height: 200px; }
        .card.card-statistic-2 .card-icon { margin: 20px 12px; }
    }
</style>
@endpush

@push('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
<script>
    $(function () {
        const $dashboard = $('#inventoryDashboard');
        const numberFormat = new Intl.NumberFormat('id-ID');
        let movementChart = null;
        let storageChart = null;

        if (!window.Chart) {
            $('#dashboardError').removeClass('d-none').text('Chart.js gagal dimuat. Periksa koneksi internet lalu muat ulang dashboard.');
            return;
        }

        movementChart = new Chart(document.getElementById('movementChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Barang Masuk',
                        data: [],
                        backgroundColor: '#63ed7a',
                        borderColor: '#63ed7a',
                        borderWidth: 1
                    },
                    {
                        label: 'Barang Keluar',
                        data: [],
                        backgroundColor: '#6777ef',
                        borderColor: '#6777ef',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { position: 'top', labels: { boxWidth: 12, usePointStyle: true } },
                scales: {
                    yAxes: [{
                        ticks: { beginAtZero: true, precision: 0 },
                        gridLines: { drawBorder: false, color: '#f2f2f2' }
                    }],
                    xAxes: [{ gridLines: { display: false } }]
                },
                tooltips: {
                    callbacks: {
                        label: function (tooltipItem, chartData) {
                            const label = chartData.datasets[tooltipItem.datasetIndex].label;
                            return label + ': ' + numberFormat.format(tooltipItem.yLabel) + ' unit';
                        }
                    }
                }
            }
        });

        storageChart = new Chart(document.getElementById('storageChart').getContext('2d'), {
            type: 'horizontalBar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Unit tersimpan',
                    data: [],
                    backgroundColor: '#6777ef',
                    borderColor: '#6777ef',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { display: false },
                scales: {
                    xAxes: [{
                        ticks: { beginAtZero: true, precision: 0 },
                        gridLines: { drawBorder: false, color: '#f2f2f2' }
                    }],
                    yAxes: [{ gridLines: { display: false } }]
                },
                tooltips: {
                    callbacks: {
                        label: function (tooltipItem) {
                            return numberFormat.format(tooltipItem.xLabel) + ' unit';
                        }
                    }
                }
            }
        });

        function loadDashboard() {
            $('#dashboardError').addClass('d-none').empty();
            $.getJSON($dashboard.data('url'), { month: $('#dashboardMonth').val() })
                .done(function (response) {
                    const data = response.data;
                    $('#storedUnits').text(numberFormat.format(data.inventory.stored_units));
                    $('#meanStorageDays').text(numberFormat.format(data.inventory.mean_storage_days));
                    $('#incomingUnits').text(numberFormat.format(data.period.incoming_units));
                    $('#outgoingUnits').text(numberFormat.format(data.period.outgoing_units));
                    $('[data-period-label]').text(data.period.label);
                    $('#pendingIncoming').text(numberFormat.format(data.pending.incoming));
                    $('#pendingOutgoing').text(numberFormat.format(data.pending.outgoing));
                    $('#pendingTotal').text(data.pending.total > 0
                        ? numberFormat.format(data.pending.total) + ' Perlu Ditinjau'
                        : 'Tidak Ada Approval Tertunda');
                    movementChart.data.labels = data.trend.map(function (item) { return item.label; });
                    movementChart.data.datasets[0].data = data.trend.map(function (item) { return item.incoming; });
                    movementChart.data.datasets[1].data = data.trend.map(function (item) { return item.outgoing; });
                    movementChart.update();
                    storageChart.data.labels = data.storage_duration.map(function (item) { return item.label; });
                    storageChart.data.datasets[0].data = data.storage_duration.map(function (item) { return item.units; });
                    storageChart.update();
                })
                .fail(function (xhr) {
                    const message = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'Data dashboard gagal dimuat. Silakan muat ulang halaman.';
                    $('#dashboardError').removeClass('d-none').text(message);
                });
        }

        $('#dashboardMonth').on('change', loadDashboard);
        loadDashboard();
    });
</script>
@endpush
