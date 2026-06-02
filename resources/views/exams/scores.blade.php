@extends('layouts.app')

@section('title', 'Batch Score Entry')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">Entrance Exam Score Entry</h3>
            <p class="text-muted m-0">Enter and update scores in bulk for all registered applicants across all subjects.</p>
        </div>
    </div>

    <!-- Filters Panel Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('exams.scores') }}" method="GET" class="row g-3 align-items-end" id="filters_form">
                <!-- Select Class -->
                <div class="col-12 col-md-5">
                    <label class="form-label fw-semibold text-muted" style="font-size: 0.75rem;">Target Admission Class <span class="text-danger">*</span></label>
                    <select class="form-select" name="class" required onchange="this.form.submit()" style="height: 42px;">
                        @foreach(\App\Models\SchoolClass::orderBy('name', 'asc')->get() as $class)
                            <option value="{{ $class->name }}" {{ $selectedClass === $class->name ? 'selected' : '' }}>{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Select Batch -->
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold text-muted" style="font-size: 0.75rem;">Exam Batch <span class="text-danger">*</span></label>
                    <select class="form-select" name="batch" required onchange="this.form.submit()" style="height: 42px;">
                        @foreach(['Batch A', 'Batch B', 'Batch C', 'Resit'] as $batchOption)
                            <option value="{{ $batchOption }}" {{ $selectedBatch === $batchOption ? 'selected' : '' }}>{{ $batchOption }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Submit Button -->
                <div class="col-12 col-md-3">
                    <button type="submit" class="btn btn-primary w-100 fw-semibold d-flex align-items-center justify-content-center gap-2" style="height: 42px;">
                        <i class="bi bi-search"></i> Load Score Sheet
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($selectedClass)
        <!-- OCR Scanner Panel -->
        @if(auth()->user()->hasRole(['Super Admin', 'Admission Officer']) && $applicants->isNotEmpty())
            <div class="card shadow-sm border-0 mb-4 bg-light border-start border-4 border-warning animate__animated animate__fadeIn">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-12 col-lg-7">
                            <h5 class="fw-bold text-dark mb-2"><i class="bi bi-lightning-charge-fill text-warning me-1"></i> AI Multi-Subject Score Sheet Scanner (OCR)</h5>
                            <p class="text-muted mb-0" style="font-size: 0.9rem;">Upload an image or PDF of the exam sheet. The AI will parse the candidate names and match scores for all subjects in columns below simultaneously.</p>
                        </div>
                        <div class="col-12 col-lg-5 mt-3 mt-lg-0 text-lg-end">
                            <div class="d-flex justify-content-lg-end align-items-center gap-2">
                                <input type="file" id="scoresheet_file" accept=".pdf,.jpeg,.png,.jpg" class="d-none">
                                <button type="button" class="btn btn-outline-warning fw-semibold d-flex align-items-center gap-2" onclick="document.getElementById('scoresheet_file').click()">
                                    <i class="bi bi-file-earmark-medical-fill"></i> Select Scan File
                                </button>
                                <button type="button" class="btn btn-warning fw-semibold d-flex align-items-center gap-2" id="btn_scan_scoresheet" disabled>
                                    <i class="bi bi-qr-code-scan"></i> Scan Sheet
                                </button>
                            </div>
                            <span class="d-block mt-2 text-muted text-start text-lg-end" id="scoresheet_file_name" style="font-size: 0.8rem;">No file chosen</span>
                        </div>
                    </div>

                    <!-- Scan progress loader -->
                    <div class="mt-3 d-none" id="scan_progress">
                        <div class="d-flex align-items-center gap-2 mb-2 text-warning">
                            <div class="spinner-border spinner-border-sm" role="status"></div>
                            <span style="font-size: 0.9rem; font-weight: 500;">Reading multi-subject score sheet and matching candidates...</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" style="width: 100%"></div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Batch Score Form -->
        <form action="{{ route('exams.scores.store') }}" method="POST">
            @csrf

            <div class="card shadow-sm border-0 mb-5">
                <div class="card-header bg-light d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 py-3">
                    <div>
                        <h6 class="m-0 fw-bold text-dark text-uppercase">
                            Score Sheet: <span class="text-primary">ALL SUBJECTS</span> ({{ $selectedClass }} - {{ $selectedBatch }})
                        </h6>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        @if($applicants->isNotEmpty())
                            <a href="{{ route('exams.scores.export.csv', ['class' => $selectedClass, 'batch' => $selectedBatch]) }}" class="btn btn-sm btn-outline-success fw-semibold d-flex align-items-center gap-1">
                                <i class="bi bi-file-earmark-spreadsheet-fill"></i> Export CSV
                            </a>
                            <a href="{{ route('exams.scores.export.pdf', ['class' => $selectedClass, 'batch' => $selectedBatch]) }}" class="btn btn-sm btn-outline-danger fw-semibold d-flex align-items-center gap-1">
                                <i class="bi bi-file-pdf-fill"></i> Export PDF
                            </a>
                        @endif
                        <span class="badge bg-primary px-3 py-2 fw-semibold">
                            {{ $applicants->count() }} Applicants Found
                        </span>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($applicants->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="font-size: 0.92rem;">
                                <thead class="table-light text-uppercase">
                                    <tr>
                                        <th class="px-4 py-3" style="width: 70px;">Sl</th>
                                        <th class="py-3">Student Name</th>
                                        <th class="py-3" style="width: 150px;">Register No</th>
                                        @foreach($subjects as $subject)
                                            <th class="py-3 text-center" style="width: 130px;">{{ $subject->name }}</th>
                                        @endforeach
                                        <th class="px-4 py-3 text-center" style="width: 120px;">Average (%)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($applicants as $index => $applicant)
                                        <tr data-student-id="{{ $applicant->id }}">
                                            <td class="px-4 text-muted">{{ $index + 1 }}</td>
                                            <td class="fw-bold text-dark text-uppercase">{{ $applicant->full_name }}</td>
                                            <td class="fw-semibold text-secondary">{{ $applicant->registration_number }}</td>
                                            
                                            @php
                                                $totalScore = 0;
                                                $subjectsCount = 0;
                                            @endphp
                                            
                                            @foreach($subjects as $subject)
                                                @php
                                                    $currentScore = $scoresMap[$applicant->id][$subject->id] ?? null;
                                                    if ($currentScore !== null) {
                                                        $totalScore += $currentScore;
                                                        $subjectsCount++;
                                                    }
                                                @endphp
                                                <td class="text-center px-2">
                                                    @if(auth()->user()->hasRole(['Super Admin', 'Admission Officer']))
                                                        <input type="number" 
                                                               min="0" 
                                                               max="100" 
                                                               class="form-control score-input text-center mx-auto" 
                                                               name="scores[{{ $applicant->id }}][{{ $subject->id }}]" 
                                                               value="{{ $currentScore }}" 
                                                               placeholder="-" 
                                                               data-subject-id="{{ $subject->id }}"
                                                               style="max-width: 90px; height: 38px;">
                                                    @else
                                                        <strong class="text-dark">{{ $currentScore ?? '-' }}</strong>
                                                    @endif
                                                </td>
                                            @endforeach
                                            
                                            <td class="px-4 text-center fw-bold text-secondary fs-6 row-average">
                                                {{ $subjectsCount > 0 ? round($totalScore / $subjectsCount, 1) . '%' : '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-people fs-1 d-block mb-3 text-secondary"></i>
                            No applicants found in the current active session for class <strong>{{ $selectedClass }}</strong>, batch <strong>{{ $selectedBatch }}</strong>.
                        </div>
                    @endif
                </div>
                
                @if($applicants->isNotEmpty() && auth()->user()->hasRole(['Super Admin', 'Admission Officer']))
                    <div class="card-footer bg-light px-4 py-3 text-end border-top">
                        <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2">
                            <i class="bi bi-save-fill"></i> Save All Exam Results
                        </button>
                    </div>
                @endif
            </div>
        </form>
    @endif
</div>
@endsection

@section('scripts')
<script>
    // Highlight input fields on focus
    document.querySelectorAll('.score-input').forEach(input => {
        input.addEventListener('focus', function() {
            this.select();
        });
        
        // Ensure values remain between 0 and 100
        input.addEventListener('input', function() {
            if (this.value !== '') {
                const val = parseInt(this.value);
                if (val < 0) this.value = 0;
                if (val > 100) this.value = 100;
            }
            const row = this.closest('tr');
            updateRowAverage(row);
        });
    });

    // Calculate row average score dynamically in real-time
    function updateRowAverage(row) {
        let total = 0;
        let count = 0;
        row.querySelectorAll('.score-input').forEach(input => {
            if (input.value !== '') {
                total += parseInt(input.value);
                count++;
            }
        });
        const averageCell = row.querySelector('.row-average');
        if (averageCell) {
            averageCell.innerText = count > 0 ? (total / count).toFixed(1) + '%' : '-';
        }
    }

    let selectedFile = null;

    // File Selector event
    const fileInput = document.getElementById('scoresheet_file');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                selectedFile = e.target.files[0];
                document.getElementById('scoresheet_file_name').innerText = selectedFile.name;
                document.getElementById('btn_scan_scoresheet').disabled = false;
            } else {
                selectedFile = null;
                document.getElementById('scoresheet_file_name').innerText = 'No file chosen';
                document.getElementById('btn_scan_scoresheet').disabled = true;
            }
        });
    }

    // Trigger Scanning Action
    const scanBtn = document.getElementById('btn_scan_scoresheet');
    if (scanBtn) {
        scanBtn.addEventListener('click', function() {
            if (!selectedFile) return;

            const progress = document.getElementById('scan_progress');
            progress.classList.remove('d-none');
            this.disabled = true;

            // Collect expected student registration numbers and names
            const expectedStudents = [];
            document.querySelectorAll('tbody tr').forEach(row => {
                const nameCell = row.querySelector('.fw-bold.text-dark');
                const regCell = row.querySelector('.text-secondary');
                
                if (nameCell && regCell) {
                    expectedStudents.push({
                        id: row.dataset.studentId,
                        name: nameCell.innerText.trim(),
                        registration_number: regCell.innerText.trim()
                    });
                }
            });

            // Collect expected subjects
            const expectedSubjects = [];
            @foreach($subjects as $sub)
                expectedSubjects.push({
                    id: "{{ $sub->id }}",
                    name: "{{ $sub->name }}"
                });
            @endforeach

            const formData = new FormData();
            formData.append('document', selectedFile);
            formData.append('expected_students', JSON.stringify(expectedStudents));
            formData.append('expected_subjects', JSON.stringify(expectedSubjects));

            fetch("{{ route('ocr.scoresheet') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Server returned an error.');
                }
                return response.json();
            })
            .then(res => {
                progress.classList.add('d-none');
                this.disabled = false;

                if (res.success && res.data) {
                    let filledCount = 0;
                    document.querySelectorAll('tbody tr').forEach(row => {
                        const regCell = row.querySelector('.text-secondary');
                        if (regCell) {
                            const regNo = regCell.innerText.trim();
                            const studentScores = res.data[regNo];
                            if (studentScores) {
                                Object.keys(studentScores).forEach(subId => {
                                    const score = studentScores[subId];
                                    const inputCell = row.querySelector('input[name="scores[' + row.dataset.studentId + '][' + subId + ']"]');
                                    if (inputCell && score !== null && score !== undefined) {
                                        inputCell.value = score;
                                        filledCount++;
                                    }
                                });
                                updateRowAverage(row);
                            }
                        }
                    });

                    alert('Scan successful! Automatically populated ' + filledCount + ' candidate score(s). Please review and save.');
                } else {
                    alert(res.error || 'Failed to extract scoresheet.');
                }
            })
            .catch(err => {
                progress.classList.add('d-none');
                this.disabled = false;
                alert(err.message || 'An error occurred during scanning.');
            });
        });
    }
</script>
@endsection
