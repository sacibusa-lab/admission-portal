<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\SmsLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TermiiService
{
    /**
     * Send SMS via Termii API.
     * Sanitizes the phone number and logs the transaction.
     */
    public function send(string $phone, string $message): array
    {
        $apiKey = Setting::get('termii_api_key');
        $senderId = Setting::get('termii_sender_id', 'SAC');

        // Sanitize phone number (Ensure correct Nigerian prefix 234... format for Termii)
        $formattedPhone = trim($phone);
        $formattedPhone = preg_replace('/[^0-9+]/', '', $formattedPhone); // Remove spaces, dashes, etc.

        if (str_starts_with($formattedPhone, '+')) {
            $formattedPhone = substr($formattedPhone, 1);
        }

        if (str_starts_with($formattedPhone, '2340')) {
            $formattedPhone = '234' . substr($formattedPhone, 4);
        } elseif (str_starts_with($formattedPhone, '0')) {
            $formattedPhone = '234' . substr($formattedPhone, 1);
        } elseif (!str_starts_with($formattedPhone, '234')) {
            $formattedPhone = '234' . $formattedPhone;
        }

        if (empty($apiKey)) {
            // Mock Mode for local testing when API credentials are not set
            $log = SmsLog::create([
                'phone' => $phone,
                'message' => $message,
                'status' => 'Sent (Mock)',
                'response' => [
                    'mock' => true,
                    'message' => 'SMS mock sent successfully.',
                    'info' => 'Termii API Key is missing. Operating in mock mode.'
                ]
            ]);

            return [
                'success' => true,
                'mock' => true,
                'log_id' => $log->id
            ];
        }

        try {
            $response = Http::timeout(15)->post('https://api.ng.termii.com/api/sms/send', [
                'api_key' => $apiKey,
                'to' => $formattedPhone,
                'from' => $senderId,
                'sms' => $message,
                'type' => 'plain',
                'channel' => 'generic'
            ]);

            $responseData = $response->json();
            
            // Check success based on Termii response keys
            $messageLower = isset($responseData['message']) ? strtolower($responseData['message']) : '';
            $status = $response->successful() && (
                isset($responseData['message_id']) || 
                str_contains($messageLower, 'sent') || 
                str_contains($messageLower, 'success') || 
                str_contains($messageLower, 'ok')
            ) ? 'Sent' : 'Failed';

            $log = SmsLog::create([
                'phone' => $phone,
                'message' => $message,
                'status' => $status,
                'response' => $responseData ?: ['raw' => $response->body()]
            ]);

            return [
                'success' => $status === 'Sent',
                'response' => $responseData,
                'log_id' => $log->id
            ];
        } catch (\Exception $e) {
            Log::error('Termii SMS Send Exception: ' . $e->getMessage());

            $log = SmsLog::create([
                'phone' => $phone,
                'message' => $message,
                'status' => 'Failed',
                'response' => ['error' => $e->getMessage()]
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'log_id' => $log->id
            ];
        }
    }
}
