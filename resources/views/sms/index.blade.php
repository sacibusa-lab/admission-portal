@extends('layouts.app')

@section('title', 'SMS Center')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold text-dark m-0">SMS Center</h3>
            <p class="text-muted m-0">Monitor outbound SMS delivery status, resend failed alerts, and send batch messages.</p>
        </div>
        <a href="{{ route('sms.batch.form') }}" class="btn btn-primary d-flex align-items-center gap-2">
            <i class="bi bi-chat-left-text-fill"></i> Send Batch SMS
        </a>
    </div>

    <!-- Search and Filter Form -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('sms.index') }}" method="GET" class="row g-2 align-items-end">
                <!-- Search bar -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold text-muted" style="font-size: 0.75rem;">Search Message or Phone</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" name="search" value="{{ request('search') }}" placeholder="Search phone number or message content...">
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="col-12 col-sm-6 col-md-4">
                    <label class="form-label fw-semibold text-muted" style="font-size: 0.75rem;">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Statuses</option>
                        <option value="Sent" {{ request('status') === 'Sent' ? 'selected' : '' }}>Sent / Delivered</option>
                        <option value="Failed" {{ request('status') === 'Failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>

                <!-- Actions -->
                <div class="col-12 col-sm-6 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary w-100 fw-semibold">
                        Filter
                    </button>
                    @if(request()->anyFilled(['search', 'status']))
                        <a href="{{ route('sms.index') }}" class="btn btn-outline-secondary" title="Clear Filters">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- SMS Logs Table -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.92rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4 py-3" style="width: 150px;">Date Sent</th>
                            <th class="py-3" style="width: 180px;">Recipient Phone</th>
                            <th class="py-3" style="width: 200px;">Candidate Profile</th>
                            <th class="py-3">Message</th>
                            <th class="py-3" style="width: 120px;">Status</th>
                            <th class="px-4 py-3 text-end" style="width: 120px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            @php
                                $matched = $applicants->get($log->phone);
                                $candidate = $matched ? $matched->first() : null;
                            @endphp
                            <tr>
                                <td class="px-4 text-muted">{{ $log->created_at->format('M d, Y H:i') }}</td>
                                <td class="fw-semibold text-dark">{{ $log->phone }}</td>
                                <td>
                                    @if($candidate)
                                        <a href="{{ route('applicants.show', $candidate->id) }}" class="link-offset-2 link-underline-opacity-0 link-underline-opacity-100-hover fw-bold text-primary">
                                            {{ $candidate->full_name }}
                                        </a>
                                        <small class="d-block text-muted" style="font-size: 0.72rem;">{{ $candidate->registration_number }} ({{ $candidate->class_applying_for }})</small>
                                    @else
                                        <span class="text-muted small">No profile matched</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="text-wrap" style="max-width: 400px; word-break: break-word;">
                                        {{ $log->message }}
                                    </div>
                                </td>
                                <td>
                                    <span class="badge {{ str_contains($log->status, 'Sent') ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' }}"
                                          title="{{ is_array($log->response) || is_object($log->response) ? json_encode($log->response) : $log->response }}"
                                          style="cursor: help;">
                                        {{ $log->status }}
                                    </span>
                                    
                                    @if(!str_contains($log->status, 'Sent') && $log->response)
                                        <div class="small text-danger mt-1" style="font-size: 0.72rem; max-width: 150px; word-break: break-all;">
                                            @if(is_array($log->response))
                                                {{ $log->response['error'] ?? $log->response['message'] ?? 'Error' }}
                                            @else
                                                {{ $log->response }}
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 text-end">
                                    @if(!str_contains($log->status, 'Sent'))
                                        <form action="{{ route('sms.resend', $log->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to resend this SMS?');" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1 ms-auto py-1 px-2" title="Resend SMS">
                                                <i class="bi bi-arrow-repeat"></i> Retry
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted small"><i class="bi bi-check-circle-fill text-success"></i> Done</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-chat-left-text fs-1 d-block mb-3 text-secondary"></i>
                                    No SMS logs found matching the filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-4 py-3 bg-light border-top d-flex justify-content-between align-items-center">
                <div class="text-muted" style="font-size: 0.85rem;">
                    Showing {{ $logs->firstItem() ?? 0 }} to {{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} records
                </div>
                <div>
                    {{ $logs->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
