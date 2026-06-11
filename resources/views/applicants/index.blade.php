@extends('layouts.app')

@section('title', 'Applicants List')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold text-dark m-0">Applicants List</h3>
            <p class="text-muted m-0">Search, filter, and manage applicant profiles and registration statuses.</p>
        </div>
        @if(auth()->user()->hasPermission('register_applicants'))
            <a href="{{ route('applicants.create') }}" class="btn btn-primary d-flex align-items-center gap-2">
                <i class="bi bi-person-plus-fill"></i> Register Applicant
            </a>
        @endif
    </div>

    <!-- Search and Filter Form -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('applicants.index') }}" method="GET" class="row g-2 align-items-end">
                <!-- Search bar -->
                <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-muted" style="font-size: 0.75rem;">Search Query</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control border-start-0 ps-0" name="search" value="{{ request('search') }}" placeholder="Registration No, Name, or Phone..." autocomplete="off">
                </div>
                <datalist id="search-suggestions"></datalist>
                </div>

                <!-- Class Filter -->
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold text-muted" style="font-size: 0.75rem;">Class</label>
                    <select class="form-select" name="class">
                        <option value="">All Classes</option>
                        @foreach(\App\Models\SchoolClass::orderBy('name', 'asc')->get() as $class)
                            <option value="{{ $class->name }}" {{ request('class') === $class->name ? 'selected' : '' }}>{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold text-muted" style="font-size: 0.75rem;">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Statuses</option>
                        <option value="Pending" {{ request('status') === 'Pending' ? 'selected' : '' }}>Pending</option>
                        <option value="Under Review" {{ request('status') === 'Under Review' ? 'selected' : '' }}>Under Review</option>
                        <option value="Exam Scheduled" {{ request('status') === 'Exam Scheduled' ? 'selected' : '' }}>Exam Scheduled</option>
                        <option value="Exam Written" {{ request('status') === 'Exam Written' ? 'selected' : '' }}>Exam Written</option>
                        <option value="Passed" {{ request('status') === 'Passed' ? 'selected' : '' }}>Passed</option>
                        <option value="Failed" {{ request('status') === 'Failed' ? 'selected' : '' }}>Failed</option>
                        <option value="Admitted" {{ request('status') === 'Admitted' ? 'selected' : '' }}>Admitted</option>
                        <option value="Rejected" {{ request('status') === 'Rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>

                <!-- Date Filter -->
                <div class="col-8 col-md-2">
                    <label class="form-label fw-semibold text-muted" style="font-size: 0.75rem;">Registration Date</label>
                    <input type="date" class="form-control" name="date" value="{{ request('date') }}">
                </div>

                <!-- Filter Actions -->
                <div class="col-4 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary w-100 fw-semibold">
                        Apply
                    </button>
                    @if(request()->anyFilled(['search', 'class', 'status', 'date']))
                        <a href="{{ route('applicants.index') }}" class="btn btn-outline-secondary" title="Clear Filters">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Applicants Table Card -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.92rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4 py-3">Reg. Number</th>
                            <th class="py-3">Photo</th>
                            <th class="py-3">Full Name</th>
                            <th class="py-3">Class</th>
                            <th class="py-3">Parent Phone</th>
                            <th class="py-3">Admission Status</th>
                            <th class="py-3">Date Registered</th>
                            <th class="px-4 py-3 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($applicants as $applicant)
                            <tr>
                                <td class="px-4 fw-bold">
                                    <a href="{{ route('applicants.show', $applicant->id) }}" class="link-offset-2 link-underline-opacity-0 link-underline-opacity-100-hover fw-bold text-primary">
                                        {{ $applicant->registration_number }}
                                    </a>
                                </td>
                                <td>
                                    @if($applicant->passport_path)
                                        <img src="{{ $applicant->passport_url }}" alt="Passport" class="rounded border" style="width: 40px; height: 40px; object-fit: cover;">
                                    @else
                                        <div class="bg-light rounded border text-muted d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="bi bi-person-fill fs-5"></i>
                                        </div>
                                    @endif
                                </td>
                                <td class="fw-bold">
                                    <a href="{{ route('applicants.show', $applicant->id) }}" class="link-offset-2 link-underline-opacity-0 link-underline-opacity-100-hover text-dark">
                                        {{ $applicant->full_name }}
                                    </a>
                                </td>
                                <td>{{ $applicant->class_applying_for }}</td>
                                <td>{{ $applicant->parent_phone_number }}</td>
                                <td>
                                    <span class="badge 
                                        @if($applicant->admission_status === 'Pending') badge-pending
                                        @elseif($applicant->admission_status === 'Under Review') badge-review
                                        @elseif($applicant->admission_status === 'Exam Scheduled') badge-exam-sch
                                        @elseif($applicant->admission_status === 'Exam Written') badge-exam-writ
                                        @elseif($applicant->admission_status === 'Passed') badge-passed
                                        @elseif($applicant->admission_status === 'Failed') badge-failed
                                        @elseif($applicant->admission_status === 'Admitted') badge-admitted
                                        @elseif($applicant->admission_status === 'Rejected') badge-rejected
                                        @endif" style="font-size: 0.75rem; padding: 0.35em 0.65em;">
                                        {{ $applicant->admission_status }}
                                    </span>
                                </td>
                                <td class="text-muted">{{ $applicant->created_at->format('M d, Y') }}</td>
                                <td class="px-4 text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-light btn-sm border" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow">
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('applicants.show', $applicant->id) }}">
                                                    <i class="bi bi-eye-fill text-primary"></i> View Profile
                                                </a>
                                            </li>
                                            @if(auth()->user()->hasPermission('register_applicants'))
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('applicants.edit', $applicant->id) }}">
                                                    <i class="bi bi-pencil-fill text-secondary"></i> Edit Details
                                                </a>
                                            </li>
                                            <li>
                                                <form action="{{ route('applicants.destroy', $applicant->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this applicant profile? This action will archive/soft-delete their record.');" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item d-flex align-items-center gap-2 text-danger">
                                                        <i class="bi bi-trash-fill text-danger"></i> Delete Profile
                                                    </button>
                                                </form>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            @endif
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('applicants.slip', $applicant->id) }}" target="_blank">
                                                    <i class="bi bi-printer-fill text-info"></i> Print Slip
                                                </a>
                                            </li>
                                            @if($applicant->admission_status === 'Admitted')
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('letters.show', $applicant->id) }}">
                                                    <i class="bi bi-file-earmark-pdf-fill text-success"></i> Admission Letter
                                                </a>
                                            </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-people fs-1 d-block mb-3 text-secondary"></i>
                                    No applicants found matching the search criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Controls -->
            <div class="px-4 py-3 bg-light border-top d-flex justify-content-between align-items-center">
                <div class="text-muted" style="font-size: 0.85rem;">
                    Showing {{ $applicants->firstItem() ?? 0 }} to {{ $applicants->lastItem() ?? 0 }} of {{ $applicants->total() }} records
                </div>
                <div>
                    {{ $applicants->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
