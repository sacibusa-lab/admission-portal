@extends('layouts.app')

@section('title', 'Send Batch SMS')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('sms.index') }}" class="btn btn-outline-secondary btn-sm rounded-circle p-2" style="width: 32px; height: 32px; line-height: 12px;" title="Back to SMS Center">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h3 class="fw-bold text-dark m-0">Send Batch SMS</h3>
            <p class="text-muted m-0">Compose a text message and broadcast it to a specific cohort of candidates.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light">
                    <h5 class="m-0 fw-bold text-dark"><i class="bi bi-chat-left-dots-fill text-primary me-2"></i>Compose Broadcast Message</h5>
                </div>
                <form action="{{ route('sms.batch.send') }}" method="POST" id="batchSmsForm">
                    @csrf
                    <div class="card-body p-4">
                        <!-- Select Cohort/Target -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">Target Recipient Group</label>
                            <select class="form-select @error('target') is-invalid @enderror" name="target" id="smsTarget" required>
                                <option value="">Select Target Cohort...</option>
                                <option value="all" data-count="{{ $totalApplicants }}">All Candidates ({{ $totalApplicants }} recipients)</option>
                                <option value="individual" data-count="0">Single/Individual Candidate</option>
                                <optgroup label="Filter by Exam Batch">
                                    @foreach($batches as $batch)
                                        <option value="{{ $batch }}" data-count="{{ $batchCounts[$batch] ?? 0 }}">
                                            {{ $batch }} ({{ $batchCounts[$batch] ?? 0 }} recipients)
                                        </option>
                                    @endforeach
                                </optgroup>
                            </select>
                            <div class="form-text text-muted mt-2" id="targetHelpText">
                                Select a group to see how many recipients will receive this broadcast.
                            </div>
                            @error('target')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Individual Applicant Search (Shown only when target is "individual") -->
                        <div class="mb-4 d-none position-relative" id="individualSearchGroup">
                            <label class="form-label fw-bold text-dark">Select Candidate</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person-search"></i></span>
                                <input type="text" class="form-control border-start-0 ps-0" id="applicantSearchInput" placeholder="Type candidate name or registration number to search..." autocomplete="off">
                            </div>
                            <input type="hidden" name="applicant_id" id="selectedApplicantId">
                            
                            <div class="list-group position-absolute shadow w-100 mt-1 d-none" id="searchResults" style="max-height: 250px; overflow-y: auto; z-index: 1050;">
                                <!-- Dynamic results -->
                            </div>
                        </div>

                        <!-- Message Body -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark">Message Text</label>
                            <textarea class="form-control @error('message') is-invalid @enderror" name="message" id="smsMessage" rows="6" maxlength="500" placeholder="Type your broadcast message here..." required></textarea>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <div class="text-muted" style="font-size: 0.82rem;">
                                    <span id="charCount">0</span> / 500 characters &bull; 
                                    <span id="pageCount" class="fw-bold text-primary">1</span> SMS page(s)
                                </div>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size: 0.75rem;">1 page = 160 chars</span>
                            </div>
                            @error('message')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Info Card -->
                        <div class="alert alert-info border-0 shadow-sm d-flex align-items-start gap-2 mb-0" style="font-size: 0.85rem;">
                            <i class="bi bi-info-circle-fill fs-5 text-info mt-1"></i>
                            <div>
                                <strong class="d-block mb-1 text-dark">Important Notes:</strong>
                                <ul class="m-0 ps-3">
                                    <li>Messages exceed 160 characters will be segmented into multiple parts by the network providers.</li>
                                    <li>SMS alerts will be **queued** and dispatched in the background to ensure your server doesn't time out.</li>
                                    <li>Verify your Termii API balance has sufficient credits before sending to large cohorts.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light px-4 py-3 d-flex justify-content-between border-top">
                        <a href="{{ route('sms.index') }}" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary d-flex align-items-center gap-2" id="submitBtn" disabled>
                            <i class="bi bi-send-fill"></i> Queue Broadcast Message (<span id="recipientCount">0</span> Recipients)
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-12 col-lg-4 mt-4 mt-lg-0">
            <div class="card shadow-sm border-0 bg-dark text-white" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-warning mb-3">Broadcast Guidelines</h5>
                    <p class="text-secondary small">Keep messages concise to reduce costs. Direct notifications should contain clear details. For example:</p>
                    
                    <div class="border border-secondary rounded p-3 bg-dark bg-opacity-20 mb-3" style="font-size: 0.82rem;">
                        <span class="text-warning fw-semibold d-block mb-1">Example Template:</span>
                        <span class="text-light">"Dear candidate, your entrance exam scores are now available. Visit the result portal at admission.saci.com.ng to print your admission slip. - Admission Office"</span>
                    </div>

                    <div class="border border-secondary rounded p-3 bg-dark bg-opacity-20 mb-3" style="font-size: 0.82rem;">
                        <span class="text-warning fw-semibold d-block mb-1">Resit Alert Template:</span>
                        <span class="text-light">"Dear candidate, the schedule for entrance exam resits is ready. Your batch is JSS1-Resit. Visit the portal to check dates. - Admin"</span>
                    </div>

                    <small class="text-secondary d-block"><i class="bi bi-lightbulb-fill text-warning me-1"></i> Ensure you test-run the message with a single candidate profile first to confirm styling and formatting.</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const targetSelect = document.getElementById('smsTarget');
    const messageInput = document.getElementById('smsMessage');
    const charCountSpan = document.getElementById('charCount');
    const pageCountSpan = document.getElementById('pageCount');
    const recipientCountSpan = document.getElementById('recipientCount');
    const submitBtn = document.getElementById('submitBtn');

    // Autocomplete elements
    const searchInput = document.getElementById('applicantSearchInput');
    const searchResults = document.getElementById('searchResults');
    const selectedIdInput = document.getElementById('selectedApplicantId');

    // Load PHP applicants list into JavaScript array
    const applicants = @json($applicantsList);

    // Handle recipient selection changes
    targetSelect.addEventListener('change', function() {
        const val = targetSelect.value;
        const selectedOption = targetSelect.options[targetSelect.selectedIndex];
        
        if (val === 'individual') {
            document.getElementById('individualSearchGroup').classList.remove('d-none');
            recipientCountSpan.innerText = '0';
            submitBtn.setAttribute('disabled', 'true');
            // Clear previous searches
            searchInput.value = '';
            selectedIdInput.value = '';
        } else {
            document.getElementById('individualSearchGroup').classList.add('d-none');
            const count = selectedOption.getAttribute('data-count') || 0;
            recipientCountSpan.innerText = count;

            if (val && count > 0) {
                submitBtn.removeAttribute('disabled');
            } else {
                submitBtn.setAttribute('disabled', 'true');
            }
        }
    });

    // Handle typing in autocomplete search input
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        searchResults.innerHTML = '';
        
        if (query.length < 1) {
            searchResults.classList.add('d-none');
            selectedIdInput.value = '';
            recipientCountSpan.innerText = '0';
            submitBtn.setAttribute('disabled', 'true');
            return;
        }

        // Filter applicants locally
        const matches = applicants.filter(app => {
            const fullName = `${app.surname} ${app.first_name} ${app.other_name || ''}`.toLowerCase();
            const regNo = app.registration_number.toLowerCase();
            return fullName.includes(query) || regNo.includes(query);
        }).slice(0, 10); // limit to 10 results for performance

        if (matches.length === 0) {
            searchResults.innerHTML = '<div class="list-group-item text-muted">No candidates found</div>';
            searchResults.classList.remove('d-none');
            selectedIdInput.value = '';
            recipientCountSpan.innerText = '0';
            submitBtn.setAttribute('disabled', 'true');
            return;
        }

        matches.forEach(app => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'list-group-item list-group-item-action text-start d-flex justify-content-between align-items-center';
            const otherName = app.other_name ? ' ' + app.other_name : '';
            const fullName = `${app.surname} ${app.first_name}${otherName}`;
            btn.innerHTML = `
                <div>
                    <strong class="text-dark d-block">${fullName}</strong>
                    <span class="text-muted small">${app.registration_number}</span>
                </div>
                <span class="badge bg-light border text-secondary">${app.parent_phone_number || 'No Phone'}</span>
            `;
            
            btn.addEventListener('click', function() {
                searchInput.value = `${fullName} (${app.registration_number})`;
                selectedIdInput.value = app.id;
                searchResults.classList.add('d-none');
                recipientCountSpan.innerText = '1';
                submitBtn.removeAttribute('disabled');
            });
            searchResults.appendChild(btn);
        });
        searchResults.classList.remove('d-none');
    });

    // Hide search results on clicking outside
    document.addEventListener('click', function(e) {
        if (!searchResults.contains(e.target) && e.target !== searchInput) {
            searchResults.classList.add('d-none');
        }
    });

    // Character counter logic
    messageInput.addEventListener('input', function() {
        const length = messageInput.value.length;
        charCountSpan.innerText = length;

        // Determine SMS pages
        let pages = 1;
        if (length > 160) {
            // Concatenated messages have a header limit of 153 chars per part
            pages = Math.ceil(length / 153);
        }
        pageCountSpan.innerText = pages;
    });
});
</script>
@endsection
