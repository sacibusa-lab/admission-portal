<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Services\AdmissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdmissionStatusController extends Controller
{
    protected AdmissionService $admissionService;

    public function __construct(AdmissionService $admissionService)
    {
        $this->admissionService = $admissionService;
    }

    /**
     * Handle admission status updates.
     */
    public function update(Request $request, $id)
    {
        $applicant = Applicant::findOrFail($id);

        $request->validate([
            'status' => 'required|in:Pending,Under Review,Exam Scheduled,Exam Written,Passed,Failed,Admitted,Rejected',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $newStatus = $request->status;
        $user = Auth::user();

        // Role Authorization: Only Principal or Super Admin can approve/reject admissions
        if (in_array($newStatus, ['Admitted', 'Rejected'])) {
            if (!$user->hasRole(['Principal', 'Super Admin'])) {
                return redirect()->back()->with('error', 'Access Denied: Only the Principal or Super Admin can approve or reject admissions.');
            }
        }

        // Change status and record timeline logs
        $this->admissionService->changeStatus($applicant, $newStatus, $request->remarks, $user->id);

        return redirect()->back()->with('success', "Applicant admission status successfully changed to: {$newStatus}.");
    }
}
