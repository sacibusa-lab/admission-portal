<?php

namespace App\Http\Controllers;

use App\Models\SmsLog;
use App\Models\Applicant;
use App\Services\SmsService;
use App\Helpers\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SmsController extends Controller
{
    protected SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Display a listing of SMS logs.
     */
    public function index(Request $request)
    {
        $query = SmsLog::query();

        if ($request->filled('status')) {
            if ($request->status === 'Sent') {
                $query->where('status', 'like', 'Sent%');
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(20);

        // Batch match phone numbers to applicants to avoid N+1 query issues
        $phones = $logs->pluck('phone')->unique();
        $applicants = Applicant::whereIn('parent_phone_number', $phones)
            ->get()
            ->groupBy('parent_phone_number');

        return view('sms.index', compact('logs', 'applicants'));
    }

    /**
     * Resend a failed SMS.
     */
    public function resend($id)
    {
        $log = SmsLog::findOrFail($id);

        if (str_contains($log->status, 'Sent')) {
            return redirect()->back()->with('error', 'This message has already been successfully sent.');
        }

        // Resend synchronously
        $result = $this->smsService->send($log->phone, $log->message);

        if ($result['success']) {
            // Delete the old failed log to keep history clean
            $log->delete();
            return redirect()->back()->with('success', 'SMS resent successfully.');
        }

        $errorMsg = isset($result['response']['message']) 
            ? $result['response']['message'] 
            : ($result['error'] ?? 'API connection failure');

        return redirect()->back()->with('error', 'Resend failed: ' . $errorMsg);
    }

    /**
     * Show the batch sending form.
     */
    public function showBatchForm()
    {
        // Get unique exam batches
        $batches = Applicant::whereNotNull('exam_batch')
            ->where('exam_batch', '<>', '')
            ->distinct()
            ->pluck('exam_batch')
            ->toArray();

        // Get counts for each batch
        $batchCounts = [];
        foreach ($batches as $batch) {
            $batchCounts[$batch] = Applicant::where('exam_batch', $batch)->count();
        }

        $totalApplicants = Applicant::count();

        return view('sms.batch', compact('batches', 'batchCounts', 'totalApplicants'));
    }

    /**
     * Dispatch SMS to a batch of applicants.
     */
    public function sendBatch(Request $request)
    {
        $request->validate([
            'target' => 'required|string',
            'message' => 'required|string|max:500',
        ]);

        $target = $request->target;
        $message = $request->message;

        if ($target === 'all') {
            $applicants = Applicant::all();
        } else {
            $applicants = Applicant::where('exam_batch', $target)->get();
        }

        if ($applicants->isEmpty()) {
            return redirect()->back()->with('error', 'No applicants found in the selected target.');
        }

        $count = 0;
        foreach ($applicants as $applicant) {
            if ($applicant->parent_phone_number) {
                // Queue the SMS
                $this->smsService->queue($applicant->parent_phone_number, $message);
                $count++;
            }
        }

        AuditLogger::log('sms_batch_sent', [
            'target' => $target,
            'recipient_count' => $count,
            'message_snippet' => substr($message, 0, 50) . '...'
        ]);

        return redirect()->route('sms.index')
            ->with('success', "Successfully queued {$count} messages for dispatch in the background.");
    }
}
