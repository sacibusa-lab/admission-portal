@extends('layouts.app')

@section('title', 'Admission Reports')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold text-dark m-0">Admission Reports</h3>
            <p class="text-muted m-0">Review enrollment success statistics, outbound communication metrics, and export data.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-warning fw-semibold d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#evaluateCutoffModal">
                <i class="bi bi-lightning-fill"></i> Evaluate Cutoff
            </button>
        </div>
    </div>

    <!-- Summary Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 border-start border-4 border-primary border-0 shadow-sm">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Total Applicants</span>
                        <h3 class="fw-bold text-dark mt-1 mb-0">{{ number_format($overviewStats['total_applicants']) }}</h3>
                    </div>
                    <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-3">
                        <i class="bi bi-people fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 border-start border-4 border-success border-0 shadow-sm">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                            Meets Cutoff ⚡
                            <i class="bi bi-info-circle text-muted" data-bs-toggle="tooltip" title="Based on exam score ≥ cutoff mark (JSS: {{ $overviewStats['junior_cutoff'] }}%, SS: {{ $overviewStats['senior_cutoff'] }}%). Updates automatically when cutoff is changed in Settings."></i>
                        </span>
                        <h3 class="fw-bold text-dark mt-1 mb-0">{{ number_format($overviewStats['admitted']) }}</h3>
                    </div>
                    <div class="bg-success bg-opacity-10 text-success rounded-3 p-3">
                        <i class="bi bi-patch-check fs-4"></i>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0 pb-2 px-3">
                    <small class="text-muted">
                        Cutoff: JSS ≥{{ $overviewStats['junior_cutoff'] }}% &middot; SS ≥{{ $overviewStats['senior_cutoff'] }}%
                        <a href="{{ route('settings.index') }}" class="text-primary text-decoration-none ms-1"><i class="bi bi-gear"></i></a>
                    </small>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 border-start border-4 border-warning border-0 shadow-sm">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Pending Review</span>
                        <h3 class="fw-bold text-dark mt-1 mb-0">{{ number_format($overviewStats['pending']) }}</h3>
                    </div>
                    <div class="bg-warning bg-opacity-10 text-warning rounded-3 p-3">
                        <i class="bi bi-hourglass-split fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 border-start border-4 border-danger border-0 shadow-sm">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Rejected</span>
                        <h3 class="fw-bold text-dark mt-1 mb-0">{{ number_format($overviewStats['rejected']) }}</h3>
                    </div>
                    <div class="bg-danger bg-opacity-10 text-danger rounded-3 p-3">
                        <i class="bi bi-person-x fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Export and Filter Controls Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-light">
            <h5 class="m-0 fw-bold text-dark">
                <i class="bi bi-file-earmark-arrow-down-fill text-primary me-2"></i>Export Filtered Applicant Data
            </h5>
        </div>
        <div class="card-body p-3">
            <form action="#" method="GET" class="row g-2 align-items-end" id="reportExportForm">
                <!-- Class -->
                <div class="col-12 col-sm-6 col-md-3 col-lg">
                    <label class="form-label fw-semibold text-muted mb-1" style="font-size: 0.75rem;">Class Applied</label>
                    <select class="form-select form-select-sm" id="filter_class">
                        <option value="">All Classes</option>
                        @foreach(\App\Models\SchoolClass::orderBy('name', 'asc')->get() as $class)
                            <option value="{{ $class->name }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status -->
                <div class="col-12 col-sm-6 col-md-3 col-lg">
                    <label class="form-label fw-semibold text-muted mb-1" style="font-size: 0.75rem;">Admission Status</label>
                    <select class="form-select form-select-sm" id="filter_status">
                        <option value="">All Statuses</option>
                        <option value="Pending">Pending</option>
                        <option value="Under Review">Under Review</option>
                        <option value="Exam Scheduled">Exam Scheduled</option>
                        <option value="Exam Written">Exam Written</option>
                        <option value="Passed">Passed</option>
                        <option value="Failed">Failed</option>
                        <option value="Admitted">Admitted</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>

                <!-- Academic Session -->
                <div class="col-12 col-sm-6 col-md-3 col-lg">
                    <label class="form-label fw-semibold text-muted mb-1" style="font-size: 0.75rem;">Academic Session</label>
                    <select class="form-select form-select-sm" id="filter_session">
                        <option value="">All Sessions</option>
                        @foreach($sessions as $session)
                            <option value="{{ $session->id }}">{{ $session->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Date From -->
                <div class="col-12 col-sm-6 col-md-3 col-lg">
                    <label class="form-label fw-semibold text-muted mb-1" style="font-size: 0.75rem;">Date From</label>
                    <input type="date" class="form-control form-control-sm" id="filter_date_from">
                </div>

                <!-- Date To -->
                <div class="col-12 col-sm-6 col-md-3 col-lg">
                    <label class="form-label fw-semibold text-muted mb-1" style="font-size: 0.75rem;">Date To</label>
                    <input type="date" class="form-control form-control-sm" id="filter_date_to">
                </div>

                <!-- Actions -->
                <div class="col-12 col-sm-6 col-md-3 col-lg-auto d-flex gap-2">
                    <button type="button" onclick="triggerExport('csv')" class="btn btn-primary btn-sm w-100 fw-semibold d-flex align-items-center justify-content-center gap-1">
                        <i class="bi bi-filetype-csv"></i> CSV
                    </button>
                    <button type="button" onclick="triggerExport('pdf')" class="btn btn-success btn-sm w-100 fw-semibold d-flex align-items-center justify-content-center gap-1">
                        <i class="bi bi-filetype-pdf"></i> PDF
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Charts Row 1: Monthly Trend + Status Doughnut -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white">
                    <h6 class="m-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-graph-up-arrow text-primary"></i> Applications Trend (Monthly)
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="monthlyTrendChart" style="max-height: 280px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white">
                    <h6 class="m-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-pie-chart-fill text-info"></i> Status Distribution
                    </h6>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <canvas id="statusDoughnutChart" style="max-height: 280px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 2: Class Distribution + Gender Breakdown -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white">
                    <h6 class="m-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-bar-chart-fill text-success"></i> Class Distribution
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="classBarChart" style="max-height: 280px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white">
                    <h6 class="m-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-gender-ambiguous text-secondary"></i> Gender Distribution
                    </h6>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <canvas id="genderPieChart" style="max-height: 280px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Row: Conversion Metrics + SMS Stats -->
    <div class="row g-4 mb-4">
        <!-- Admission Success Rate -->
        <div class="col-12 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white">
                    <h6 class="m-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-trophy-fill text-warning"></i> Enrollment Conversion Metrics
                        <span class="badge bg-warning bg-opacity-10 text-dark ms-auto" style="font-size: 0.7rem;">
                            <i class="bi bi-lightning-fill"></i> Cutoff-based
                        </span>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="text-center py-3">
                        <span class="text-muted d-block" style="font-size: 0.88rem; font-weight: 500;">SUCCESS RATE (by cutoff)</span>
                        <h1 class="fw-bold mt-1 text-primary" style="font-size: 3.5rem;">{{ $successStats['rate'] }}%</h1>
                        <p class="text-muted mt-2 mb-0" style="font-size: 0.9rem;">
                            {{ $successStats['admitted'] }} students meet the cutoff out of {{ $successStats['total'] }} total applicants.
                        </p>
                        <div class="d-flex justify-content-center gap-3 mt-2">
                            <small class="text-muted"><span class="badge bg-primary bg-opacity-10 text-primary">JSS ≥ {{ $successStats['junior_cutoff'] }}%</span></small>
                            <small class="text-muted"><span class="badge bg-success bg-opacity-10 text-success">SS ≥ {{ $successStats['senior_cutoff'] }}%</span></small>
                        </div>
                    </div>
                    <div class="progress mt-3 mb-2" style="height: 10px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $successStats['rate'] }}%" aria-valuenow="{{ $successStats['rate'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="row text-center mt-4 g-2">
                        <div class="col-4">
                            <div class="bg-light rounded-3 p-2">
                                <div class="fw-bold text-dark fs-5">{{ $overviewStats['exam_scheduled'] }}</div>
                                <small class="text-muted">Exam Scheduled</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bg-light rounded-3 p-2">
                                <div class="fw-bold text-dark fs-5">{{ $overviewStats['pending'] }}</div>
                                <small class="text-muted">Pending Review</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bg-light rounded-3 p-2">
                                <div class="fw-bold text-dark fs-5">{{ $overviewStats['rejected'] }}</div>
                                <small class="text-muted">Rejected</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SMS Communication Usage -->
        <div class="col-12 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white">
                    <h6 class="m-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-chat-dots-fill text-secondary"></i> Communication Logs Ratio
                    </h6>
                </div>
                <div class="card-body">
                    @php
                        $smsTotal = array_sum(array_column($smsStats, 'total'));
                    @endphp
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="bg-secondary bg-opacity-10 rounded-3 p-3">
                            <i class="bi bi-envelope-paper fs-2 text-secondary"></i>
                        </div>
                        <div>
                            <span class="text-muted d-block" style="font-size: 0.85rem;">Total Messages Processed</span>
                            <span class="fw-bold text-dark fs-3">{{ $smsTotal }}</span>
                        </div>
                    </div>
                    <div class="list-group list-group-flush">
                        @forelse($smsStats as $stat)
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">
                                    <i class="bi bi-chevron-right me-2"></i>
                                    <span class="badge 
                                        @if($stat['status'] === 'sent') bg-success 
                                        @elseif($stat['status'] === 'failed') bg-danger 
                                        @else bg-secondary 
                                        @endif bg-opacity-10 text-dark me-1">
                                        {{ $stat['status'] }}
                                    </span>
                                </span>
                                <span class="fw-bold text-dark">{{ $stat['total'] }}</span>
                            </div>
                        @empty
                            <div class="text-center py-4 text-muted">No SMS history logged.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Inline Applicant Data Table -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h6 class="m-0 fw-bold text-dark d-flex align-items-center gap-2">
                <i class="bi bi-table text-primary"></i> Recent Applicants
                <span class="badge bg-primary bg-opacity-10 text-primary fs-6">{{ $recentApplicants->count() }}</span>
            </h6>
            <div class="d-flex gap-2">
                <input type="text" id="tableSearch" class="form-control form-control-sm" placeholder="Search by name or reg. no..." style="width: 260px;">
                <button class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('tableSearch').value=''; filterTable();">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="applicantsTable" style="font-size: 0.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="px-3 py-3">Reg. No.</th>
                            <th class="py-3">Full Name</th>
                            <th class="py-3">Class</th>
                            <th class="py-3">Gender</th>
                            <th class="py-3">Status</th>
                            <th class="py-3">Session</th>
                            <th class="px-3 py-3 text-end">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentApplicants as $app)
                            <tr class="applicant-row">
                                <td class="px-3 text-muted fw-semibold">{{ $app->registration_number }}</td>
                                <td>
                                    <span class="fw-semibold text-dark">{{ $app->full_name }}</span>
                                </td>
                                <td>{{ $app->class_applying_for }}</td>
                                <td>
                                    <span class="badge 
                                        @if($app->gender === 'Male') bg-primary 
                                        @elseif($app->gender === 'Female') bg-danger 
                                        @else bg-secondary 
                                        @endif bg-opacity-10 text-dark">
                                        {{ $app->gender ?? '—' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge 
                                        @if($app->admission_status === 'Admitted') bg-success 
                                        @elseif($app->admission_status === 'Rejected') bg-danger 
                                        @elseif($app->admission_status === 'Pending') bg-secondary 
                                        @elseif(in_array($app->admission_status, ['Passed', 'Exam Written'])) bg-info 
                                        @else bg-primary 
                                        @endif" style="font-size: 0.75rem;">
                                        {{ $app->admission_status }}
                                    </span>
                                </td>
                                <td class="text-muted">{{ $app->academicSession?->name ?? '—' }}</td>
                                <td class="px-3 text-end text-muted">{{ $app->created_at->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No applicants found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($recentApplicants->count() >= 50)
            <div class="card-footer bg-white text-center text-muted py-2" style="font-size: 0.85rem;">
                Showing latest 50 records. Use export for full data.
            </div>
        @endif
    </div>
</div>

<!-- Evaluate Cutoff Confirmation Modal -->
<div class="modal fade" id="evaluateCutoffModal" tabindex="-1" aria-labelledby="evaluateCutoffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning bg-opacity-10 border-0">
                <h5 class="modal-title fw-bold" id="evaluateCutoffModalLabel">
                    <i class="bi bi-lightning-fill text-warning me-2"></i>Evaluate Admission Cutoff
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">This will evaluate <strong>all applicants</strong> with exam scores against the current cutoff marks:</p>
                <div class="bg-light rounded-3 p-3 mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Junior Cutoff (JSS):</span>
                        <span class="fw-bold text-primary">≥ {{ $overviewStats['junior_cutoff'] }}%</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Senior Cutoff (SS):</span>
                        <span class="fw-bold text-success">≥ {{ $overviewStats['senior_cutoff'] }}%</span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between">
                        <span>Total applicants with scores:</span>
                        <span class="fw-bold">{{ $overviewStats['total_applicants'] }}</span>
                    </div>
                </div>
                <div class="alert alert-warning mb-0 py-2" style="font-size: 0.88rem;">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Admission statuses will be updated to <strong>Admitted</strong> or <strong>Failed</strong>.
                    Applicants without exam scores will be skipped.
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning fw-semibold" id="runEvaluateBtn" onclick="runCutoffEvaluation()">
                    <i class="bi bi-play-fill"></i> Run Evaluation
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Result Toast -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
    <div id="evaluateToast" class="toast border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="6000">
        <div class="toast-header">
            <i class="bi bi-check-circle-fill text-success me-2" id="toastIcon"></i>
            <strong class="me-auto" id="toastTitle">Evaluation Complete</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body" id="toastMessage"></div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // ── Bootstrap tooltips ──
    document.addEventListener('DOMContentLoaded', function () {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el); });
    });

    // ── Evaluate Cutoff ──
    async function runCutoffEvaluation() {
        const btn = document.getElementById('runEvaluateBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Evaluating...';

        try {
            const res = await fetch('{{ route('reports.evaluate.cutoff') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                }
            });
            const data = await res.json();

            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('evaluateCutoffModal')).hide();

            // Show toast
            const toast = new bootstrap.Toast(document.getElementById('evaluateToast'));
            document.getElementById('toastTitle').textContent = data.success ? '✅ Evaluation Complete' : '❌ Evaluation Failed';
            document.getElementById('toastIcon').className = data.success ? 'bi bi-check-circle-fill text-success me-2' : 'bi bi-x-circle-fill text-danger me-2';
            document.getElementById('toastMessage').textContent = data.message;

            toast.show();

            if (data.success) {
                // Reload page after a short delay to reflect updated stats
                setTimeout(() => window.location.reload(), 2000);
            }
        } catch (err) {
            document.getElementById('toastTitle').textContent = '❌ Network Error';
            document.getElementById('toastIcon').className = 'bi bi-x-circle-fill text-danger me-2';
            document.getElementById('toastMessage').textContent = 'Could not reach the server. Please try again.';
            new bootstrap.Toast(document.getElementById('evaluateToast')).show();
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-play-fill"></i> Run Evaluation';
        }
    }

    // ── Export trigger ──
    function triggerExport(type) {
        const cls = document.getElementById('filter_class').value;
        const status = document.getElementById('filter_status').value;
        const session = document.getElementById('filter_session').value;
        const dateFrom = document.getElementById('filter_date_from').value;
        const dateTo = document.getElementById('filter_date_to').value;

        const params = new URLSearchParams();
        if(cls) params.append('class', cls);
        if(status) params.append('status', status);
        if(session) params.append('session', session);
        if(dateFrom) params.append('date_from', dateFrom);
        if(dateTo) params.append('date_to', dateTo);

        let baseUrl = '';
        if(type === 'csv') {
            baseUrl = "{{ route('reports.export.csv') }}";
        } else if(type === 'pdf') {
            baseUrl = "{{ route('reports.export.pdf') }}";
        }

        window.location.href = baseUrl + '?' + params.toString();
    }

    // ── Table search ──
    document.getElementById('tableSearch')?.addEventListener('keyup', filterTable);
    function filterTable() {
        const q = document.getElementById('tableSearch').value.toLowerCase();
        document.querySelectorAll('.applicant-row').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    }

    // ── Chart.js Color palette ──
    const COLORS = {
        primary:   '#0B5ED7',
        success:   '#198754',
        warning:   '#FFC107',
        danger:    '#DC3545',
        info:      '#0DCAF0',
        secondary: '#6C757D',
        dark:      '#212529',
    };

    const STATUS_COLORS = {
        'Admitted':      COLORS.success,
        'Rejected':      COLORS.danger,
        'Pending':       COLORS.secondary,
        'Under Review':  COLORS.warning,
        'Exam Scheduled':COLORS.info,
        'Exam Written':  '#0D6EFD',
        'Passed':        '#198754',
        'Failed':        '#DC3545',
    };

    // ── 1. Monthly Trend Line Chart ──
    (function() {
        const labels = {!! json_encode(array_column($monthStats, 'label')) !!};
        const values = {!! json_encode(array_column($monthStats, 'total')) !!};
        const ctx = document.getElementById('monthlyTrendChart');
        if (!ctx) return;
        new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: labels.length ? labels : ['No Data'],
                datasets: [{
                    label: 'Registered Students',
                    data: values.length ? values : [0],
                    borderColor: COLORS.primary,
                    backgroundColor: 'rgba(11, 94, 215, 0.06)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: COLORS.primary,
                    pointRadius: 4,
                    pointHoverRadius: 7,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } },
                    x: { grid: { display: false } }
                }
            }
        });
    })();

    // ── 2. Status Doughnut Chart ──
    (function() {
        const data = {!! json_encode($statusStats) !!};
        const ctx = document.getElementById('statusDoughnutChart');
        if (!ctx) return;
        if (!data.length) {
            ctx.parentElement.innerHTML = '<div class="text-center text-muted py-4">No data</div>';
            return;
        }
        new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: data.map(r => r.admission_status),
                datasets: [{
                    data: data.map(r => r.total),
                    backgroundColor: data.map(r => STATUS_COLORS[r.admission_status] || COLORS.primary),
                    borderWidth: 2,
                    borderColor: '#fff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12, font: { size: 11 } } }
                }
            }
        });
    })();

    // ── 3. Class Distribution Bar Chart ──
    (function() {
        const data = {!! json_encode($classStats) !!};
        const ctx = document.getElementById('classBarChart');
        if (!ctx) return;
        if (!data.length) {
            ctx.parentElement.innerHTML = '<div class="text-center text-muted py-4">No data</div>';
            return;
        }
        new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: data.map(r => r.class_applying_for),
                datasets: [{
                    label: 'Applicants',
                    data: data.map(r => r.total),
                    backgroundColor: [
                        'rgba(11, 94, 215, 0.7)',
                        'rgba(25, 135, 84, 0.7)',
                        'rgba(255, 193, 7, 0.7)',
                        'rgba(220, 53, 69, 0.7)',
                        'rgba(13, 202, 240, 0.7)',
                        'rgba(108, 117, 125, 0.7)',
                    ],
                    borderWidth: 0,
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } },
                    x: { grid: { display: false } }
                }
            }
        });
    })();

    // ── 4. Gender Pie Chart ──
    (function() {
        const data = {!! json_encode($genderStats) !!};
        const ctx = document.getElementById('genderPieChart');
        if (!ctx) return;
        if (!data.length) {
            ctx.parentElement.innerHTML = '<div class="text-center text-muted py-4">No data</div>';
            return;
        }
        new Chart(ctx.getContext('2d'), {
            type: 'pie',
            data: {
                labels: data.map(r => r.gender),
                datasets: [{
                    data: data.map(r => r.total),
                    backgroundColor: ['rgba(13, 110, 253, 0.8)', 'rgba(220, 53, 69, 0.8)', 'rgba(108, 117, 125, 0.5)'],
                    borderWidth: 2,
                    borderColor: '#fff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12, font: { size: 11 } } }
                }
            }
        });
    })();
</script>
@endsection
