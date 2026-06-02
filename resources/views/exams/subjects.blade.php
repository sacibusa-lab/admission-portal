@extends('layouts.app')

@section('title', 'Exam Subjects')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">Entrance Exam Subjects</h3>
            <p class="text-muted m-0">Configure and manage subjects evaluated in the school entrance examination.</p>
        </div>
    </div>

    <div class="row g-4">
        @if(auth()->user()->hasRole(['Super Admin', 'Admission Officer']))
        <!-- Add Subject Card -->
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light">
                    <h5 class="m-0 fw-bold text-dark">Add New Subject</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('exams.subjects.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Subject Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required placeholder="e.g. Quantitative Reasoning">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-plus-circle-fill"></i> Add Exam Subject
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endif

        <!-- Subjects List Table Card -->
        <div class="col-12 {{ auth()->user()->hasRole(['Super Admin', 'Admission Officer']) ? 'col-lg-8' : 'col-lg-12' }}">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light">
                    <h5 class="m-0 fw-bold text-dark">Configured Subjects</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.92rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="px-4 py-3" style="width: 80px;">S/N</th>
                                    <th class="py-3">Subject Name</th>
                                    <th class="py-3">Recorded Scores</th>
                                    @if(auth()->user()->hasRole(['Super Admin', 'Admission Officer']))
                                        <th class="px-4 py-3 text-end" style="width: 150px;">Actions</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($subjects as $index => $subject)
                                    <tr>
                                        <td class="px-4 text-muted">{{ $index + 1 }}</td>
                                        <td class="fw-bold text-dark">{{ $subject->name }}</td>
                                        <td>
                                            @if($subject->scores_count > 0)
                                                <span class="badge bg-success bg-opacity-10 text-success fw-semibold">{{ $subject->scores_count }} students graded</span>
                                            @else
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary">No scores entered</span>
                                            @endif
                                        </td>
                                        @if(auth()->user()->hasRole(['Super Admin', 'Admission Officer']))
                                            <td class="px-4 text-end">
                                                @if($subject->scores_count === 0)
                                                    <form action="{{ route('exams.subjects.destroy', $subject->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this exam subject?');" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger btn-sm px-2 py-1" title="Delete Subject">
                                                            <i class="bi bi-trash3-fill"></i> Delete
                                                        </button>
                                                    </form>
                                                @else
                                                    <button type="button" class="btn btn-light btn-sm px-2 py-1 text-muted" disabled title="Cannot delete: scores exist">
                                                        <i class="bi bi-lock-fill"></i> Locked
                                                    </button>
                                                @endif
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ auth()->user()->hasRole(['Super Admin', 'Admission Officer']) ? '4' : '3' }}" class="text-center py-5 text-muted">
                                            <i class="bi bi-journal-x fs-1 d-block mb-3 text-secondary"></i>
                                            No exam subjects configured yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
