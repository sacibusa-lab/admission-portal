@extends('layouts.app')

@section('title', 'Admission Reports')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold text-dark m-0">Admission Reports</h3>
            <p class="text-muted m-0">Review enrollment success statistics, outbound communication metrics, and export data.</p>
        </div>
    </div>

    <!-- Export and Filter Controls Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-light">
            <h5 class="m-0 fw-bold text-dark"><i class="bi bi-file-earmark-arrow-down-fill text-primary me-2"></i>Export Filtered Applicant Data</h5>
        </div>
        <div class="card-body p-3">
            <form action="#" method="GET" class="row g-2 align-items-end" id="reportExportForm">
                <!-- Class -->
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label fw-semibold text-muted mb-1" style="font-size: 0.75rem;">Class Applied</label>
                    <select class="form-select" id="filter_class">
                        <option value="">All Classes</option>
                        @foreach(\App\Models\SchoolClass::orderBy('name', 'asc')->get() as $class)
                            <option value="{{ $class->name }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status -->
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label fw-semibold text-muted mb-1" style="font-size: 0.75rem;">Admission Status</label>
                    <select class="form-select" id="filter_status">
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

                <!-- Date -->
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label fw-semibold text-muted mb-1" style="font-size: 0.75rem;">Registration Date</label>
                    <input type="date" class="form-control" id="filter_date">
                </div>

                <!-- Actions -->
                <div class="col-12 col-sm-6 col-md-3 d-flex gap-2">
                    <button type="button" onclick="triggerExport('csv')" class="btn btn-primary w-100 fw-semibold d-flex align-items-center justify-content-center gap-1">
                        <i class="bi bi-filetype-csv"></i> Export CSV
                    </button>
                    <button type="button" onclick="triggerExport('pdf')" class="btn btn-success w-100 fw-semibold d-flex align-items-center justify-content-center gap-1">
                        <i class="bi bi-filetype-pdf"></i> Export PDF
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reports Analysis Grid -->
    <div class="row g-4">
        <!-- Admission Success Rate -->
        <div class="col-12 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header">
                    <h6 class="m-0 fw-bold text-dark">Enrollment Conversion Metrics</h6>
                </div>
                <div class="card-body">
                    <div class="text-center py-3">
                        <span class="text-muted d-block" style="font-size: 0.88rem; font-weight: 500;">SUCCESS RATE</span>
                        <h1 class="fw-bold mt-1 text-primary" style="font-size: 3.5rem;">{{ $successStats['rate'] }}%</h1>
                        <p class="text-muted mt-2 mb-0" style="font-size: 0.9rem;">
                            {{ $successStats['admitted'] }} students admitted out of {{ $successStats['total'] }} total applications.
                        </p>
                    </div>

                    <!-- Progress bar -->
                    <div class="progress mt-3 mb-2" style="height: 10px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $successStats['rate'] }}%" aria-valuenow="{{ $successStats['rate'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SMS Communication Usage -->
        <div class="col-12 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header">
                    <h6 class="m-0 fw-bold text-dark">Communication Logs Ratio</h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @php
                            $smsTotal = array_sum(array_column($smsStats, 'total'));
                        @endphp
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                            <span class="text-dark fw-semibold"><i class="bi bi-chat-dots-fill text-muted me-2"></i>Total Messages Processed</span>
                            <span class="badge bg-secondary fs-6">{{ $smsTotal }}</span>
                        </div>
                        @forelse($smsStats as $stat)
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted"><i class="bi bi-chevron-right me-2"></i>{{ $stat['status'] }}</span>
                                <span class="fw-bold text-dark">{{ $stat['total'] }}</span>
                            </div>
                        @empty
                            <div class="text-center py-4 text-muted">No SMS history logged.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Breakdown Progress -->
        <div class="col-12 col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header">
                    <h6 class="m-0 fw-bold text-dark">Admission Status Breakdown</h6>
                </div>
                <div class="card-body">
                    @php
                        $applicantTotal = array_sum(array_column($statusStats, 'total'));
                    @endphp
                    @forelse($statusStats as $stat)
                        @php
                            $percent = $applicantTotal > 0 ? round(($stat['total'] / $applicantTotal) * 100, 1) : 0;
                        @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 0.88rem;">
                                <span class="fw-semibold text-muted">{{ $stat['admission_status'] }}</span>
                                <span class="fw-bold text-dark">{{ $stat['total'] }} ({{ $percent }}%)</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar 
                                    @if($stat['admission_status'] === 'Admitted') bg-success 
                                    @elseif($stat['admission_status'] === 'Rejected') bg-danger 
                                    @elseif($stat['admission_status'] === 'Pending') bg-secondary 
                                    @else bg-primary 
                                    @endif" role="progressbar" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted">No applicants registered yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Class Breakdown list -->
        <div class="col-12 col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header">
                    <h6 class="m-0 fw-bold text-dark">Class Distribution Breakdown</h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @php
                            $classTotal = array_sum(array_column($classStats, 'total'));
                        @endphp
                        @forelse($classStats as $stat)
                            @php
                                $percent = $classTotal > 0 ? round(($stat['total'] / $classTotal) * 100, 1) : 0;
                            @endphp
                            <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <strong class="text-dark d-block">{{ $stat['class_applying_for'] }}</strong>
                                    <small class="text-muted">{{ $stat['total'] }} applicants registered</small>
                                </div>
                                <span class="badge bg-primary bg-opacity-10 text-primary fs-6">{{ $percent }}%</span>
                            </div>
                        @empty
                            <div class="text-center py-4 text-muted">No applications received.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function triggerExport(type) {
        const cls = document.getElementById('filter_class').value;
        const status = document.getElementById('filter_status').value;
        const date = document.getElementById('filter_date').value;

        // Build URL parameters
        const params = new URLSearchParams();
        if(cls) params.append('class', cls);
        if(status) params.append('status', status);
        if(date) params.append('date', date);

        let baseUrl = '';
        if(type === 'csv') {
            baseUrl = "{{ route('reports.export.csv') }}";
        } else if(type === 'pdf') {
            baseUrl = "{{ route('reports.export.pdf') }}";
        }

        const exportUrl = baseUrl + '?' + params.toString();
        window.location.href = exportUrl;
    }
</script>
@endsection
