@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid p-0">
    <!-- Header Greeting -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">Portal Dashboard</h3>
            <p class="text-muted m-0">Welcome back, {{ auth()->user()->name }}. Here is today's overview.</p>
        </div>
        <div class="bg-white px-3 py-2 border rounded-3 d-flex align-items-center gap-2 shadow-sm">
            <i class="bi bi-calendar3 text-primary"></i>
            <span class="fw-semibold text-secondary" style="font-size: 0.9rem;">{{ today()->format('l, jS F Y') }}</span>
        </div>
    </div>

    <!-- Stats Cards Grid -->
    <div class="row g-3 mb-4">
        <!-- Total Applicants -->
        <div class="col-12 col-sm-6 col-xl-3 col-xxl-3">
            <div class="card card-hoverable h-100 border-start border-4 border-primary">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Total Applicants</span>
                        <h3 class="fw-bold text-dark mt-1 mb-0">{{ number_format($stats['total_applicants']) }}</h3>
                    </div>
                    <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-3">
                        <i class="bi bi-people fs-4" style="line-height: 1;"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Applicants Today -->
        <div class="col-12 col-sm-6 col-xl-3 col-xxl-3">
            <div class="card card-hoverable h-100 border-start border-4 border-info">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Applicants Today</span>
                        <h3 class="fw-bold text-dark mt-1 mb-0">{{ number_format($stats['applicants_today']) }}</h3>
                    </div>
                    <div class="bg-info bg-opacity-10 text-info rounded-3 p-3">
                        <i class="bi bi-person-check fs-4" style="line-height: 1;"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Review -->
        <div class="col-12 col-sm-6 col-xl-3 col-xxl-3">
            <div class="card card-hoverable h-100 border-start border-4 border-warning">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Pending Review</span>
                        <h3 class="fw-bold text-dark mt-1 mb-0">{{ number_format($stats['pending_review']) }}</h3>
                    </div>
                    <div class="bg-warning bg-opacity-10 text-warning rounded-3 p-3">
                        <i class="bi bi-hourglass-split fs-4" style="line-height: 1;"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Exam Scheduled -->
        <div class="col-12 col-sm-6 col-xl-3 col-xxl-3">
            <div class="card card-hoverable h-100 border-start border-4 border-info">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Exam Scheduled</span>
                        <h3 class="fw-bold text-dark mt-1 mb-0">{{ number_format($stats['exam_scheduled']) }}</h3>
                    </div>
                    <div class="bg-info bg-opacity-10 text-info rounded-3 p-3">
                        <i class="bi bi-calendar-check fs-4" style="line-height: 1;"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admitted Students -->
        <div class="col-12 col-sm-6 col-xl-3 col-xxl-3">
            <div class="card card-hoverable h-100 border-start border-4 border-success">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Admitted</span>
                        <h3 class="fw-bold text-dark mt-1 mb-0">{{ number_format($stats['admitted_students']) }}</h3>
                    </div>
                    <div class="bg-success bg-opacity-10 text-success rounded-3 p-3">
                        <i class="bi bi-patch-check fs-4" style="line-height: 1;"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Failed -->
        <div class="col-12 col-sm-6 col-xl-3 col-xxl-3">
            <div class="card card-hoverable h-100 border-start border-4 border-danger">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Failed</span>
                        <h3 class="fw-bold text-dark mt-1 mb-0">{{ number_format($stats['failed_students']) }}</h3>
                    </div>
                    <div class="bg-danger bg-opacity-10 text-danger rounded-3 p-3">
                        <i class="bi bi-x-circle fs-4" style="line-height: 1;"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rejected Students -->
        <div class="col-12 col-sm-6 col-xl-3 col-xxl-3">
            <div class="card card-hoverable h-100 border-start border-4 border-secondary">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Rejected</span>
                        <h3 class="fw-bold text-dark mt-1 mb-0">{{ number_format($stats['rejected_students']) }}</h3>
                    </div>
                    <div class="bg-secondary bg-opacity-10 text-secondary rounded-3 p-3">
                        <i class="bi bi-person-x fs-4" style="line-height: 1;"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- SMS Sent Today -->
        <div class="col-12 col-sm-6 col-xl-3 col-xxl-3">
            <div class="card card-hoverable h-100 border-start border-4 border-dark">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.5px;">SMS Logs Today</span>
                        <h3 class="fw-bold text-dark mt-1 mb-0">{{ number_format($stats['sms_sent_today']) }}</h3>
                    </div>
                    <div class="bg-dark bg-opacity-10 text-dark rounded-3 p-3">
                        <i class="bi bi-chat-left-dots fs-4" style="line-height: 1;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
        <!-- Monthly Trend -->
        <div class="col-12 col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="m-0 fw-bold text-dark">Applications Trend</h5>
                    <span class="text-muted" style="font-size: 0.8rem;">By registration month</span>
                </div>
                <div class="card-body">
                    <canvas id="monthlyTrendChart" style="max-height: 320px;"></canvas>
                </div>
            </div>
        </div>

        <!-- Class Breakdown -->
        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="m-0 fw-bold text-dark">Class Breakdown</h5>
                    <span class="text-muted" style="font-size: 0.8rem;">Count by Class</span>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <div class="w-100">
                        <canvas id="classBreakdownChart" style="max-height: 320px;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity Feed -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="m-0 fw-bold text-dark">System Activities Log</h5>
            <span class="badge bg-secondary">Real-time Feed</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4 py-3" style="width: 150px;">Timestamp</th>
                            <th class="py-3">User</th>
                            <th class="py-3">Action</th>
                            <th class="py-3">Details</th>
                            <th class="px-4 py-3 text-end" style="width: 150px;">IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentActivities as $activity)
                            <tr>
                                <td class="px-4 text-muted">{{ $activity->created_at->format('M d, H:i') }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.75rem; font-weight: 600;">
                                            {{ strtoupper(substr($activity->user ? $activity->user->name : 'SYS', 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-dark">{{ $activity->user ? $activity->user->name : 'System Action' }}</div>
                                            <small class="text-muted" style="font-size: 0.72rem;">{{ $activity->user ? $activity->user->role->name : 'Background' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge 
                                        @if(str_contains($activity->action, 'register')) bg-success bg-opacity-10 text-success
                                        @elseif(str_contains($activity->action, 'update')) bg-primary bg-opacity-10 text-primary
                                        @elseif(str_contains($activity->action, 'delete')) bg-danger bg-opacity-10 text-danger
                                        @elseif(str_contains($activity->action, 'login')) bg-info bg-opacity-10 text-info
                                        @else bg-secondary bg-opacity-10 text-secondary
                                        @endif" style="font-size: 0.75rem; font-weight: 500;">
                                        {{ str_replace('_', ' ', strtoupper($activity->action)) }}
                                    </span>
                                </td>
                                <td class="text-muted text-truncate" style="max-width: 320px;">
                                    @if(is_array($activity->details))
                                        {{ json_encode($activity->details) }}
                                    @else
                                        {{ $activity->details }}
                                    @endif
                                </td>
                                <td class="px-4 text-end text-muted">{{ $activity->ip_address }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No recent activities recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // 1. Applications Trend Line Chart
    const monthlyCtx = document.getElementById('monthlyTrendChart').getContext('2d');
    const monthlyLabels = {!! json_encode(array_column($monthData, 'label')) !!};
    const monthlyValues = {!! json_encode(array_column($monthData, 'total')) !!};
    
    // Fallback data if database is empty
    const trendLabels = monthlyLabels.length > 0 ? monthlyLabels : ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    const trendValues = monthlyValues.length > 0 ? monthlyValues : [0, 0, 0, 0, 0, 0];

    new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: trendLabels,
            datasets: [{
                label: 'Registered Students',
                data: trendValues,
                borderColor: '#0B5ED7',
                backgroundColor: 'rgba(11, 94, 215, 0.05)',
                borderWidth: 3,
                fill: true,
                tension: 0.3,
                pointBackgroundColor: '#0B5ED7',
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });

    // 2. Class Breakdown Bar Chart
    const classCtx = document.getElementById('classBreakdownChart').getContext('2d');
    const classRows = {!! json_encode($classData) !!};
    const classLabels = classRows.map(r => r.class_applying_for);
    const classValues = classRows.map(r => r.total);

    const breakdownLabels = classLabels.length > 0 ? classLabels : ['JSS1', 'JSS2', 'SS1'];
    const breakdownValues = classValues.length > 0 ? classValues : [0, 0, 0];

    new Chart(classCtx, {
        type: 'bar',
        data: {
            labels: breakdownLabels,
            datasets: [{
                data: breakdownValues,
                backgroundColor: [
                    '#0B5ED7', // Primary
                    '#198754', // Success
                    '#FFC107'  // Accent
                ],
                borderWidth: 0,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });
</script>
@endsection
