<?php

// Package namespace.
namespace NotificationChannels\Ippanel;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr; // Helper for arrays
use Exception; // Import the Exception class
use DateTimeInterface; // Import DateTimeInterface

class IppanelChannel
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     * @throws \Exception
     */
    public function send($notifiable, Notification $notification)
    {
        // Ensure the notification has a toIppanel method and it returns an IppanelMessage instance.
        if (!method_exists($notification, 'toIppanel')) {
            throw new Exception('Notification does not have a toIppanel method.');
        }

        /** @var \NotificationChannels\Ippanel\IppanelMessage $message */
        $message = $notification->toIppanel($notifiable);

        // Retrieve the recipient number(s) from the notifiable model.
        // The notifiable model must implement routeNotificationForIppanel.
        $to = $notifiable->routeNotificationForIppanel($this);

        if (empty($to)) {
            Log::info('IPPanel Notification skipped: No recipient found for notifiable.');
            return; // No recipient defined for this notification type.
        }

        // Ensure $to is an array.
        $to = Arr::wrap($to); // Use Arr::wrap to convert to array if necessary.

        // Get configuration from the package's config file.
        $apiKey = config('ippanel.api_key');
        // Use the message sender number if set, otherwise use the default from config.
        $senderNumber = $message->from ?? config('ippanel.sender_number');

        // --- Verify these API details against the IPPanel Swagger documentation ---
        // Base API endpoint from config.
        $baseEndpoint = config('ippanel.endpoint', 'https://api.ippanel.com/v1'); // Assuming base endpoint is v1

        // Determine the specific endpoint based on message type (simple or pattern).
        // Based on Swagger for simple send: /sms/send/webservice/single
        // You MUST verify the pattern send path from the Swagger documentation.
        $apiPath = $message->isPatternBased() ? '/messages/patterns/send' : '/sms/send/webservice/single'; // Updated simple send path

        $apiEndpoint = rtrim($baseEndpoint, '/') . '/' . ltrim($apiPath, '/');


        // Prepare the request body based on whether it's a simple text message or pattern-based.
        // The parameter names and structure MUST exactly match the IPPanel API documentation.
        $requestBody = [
            'recipient' => $to, // Array of recipient numbers
            'sender' => $senderNumber, // Sender number
        ];

        if ($message->isPatternBased()) {
             // Prepare data for pattern-based SMS.
             // Verify parameter names like 'pattern_code' and 'variables' from Swagger.
             $requestBody['pattern_code'] = $message->patternCode;
             $requestBody['variables'] = $message->variables; // Should be an array like ['name' => 'value']
             // Add any other parameters required by IPPanel's pattern API (e.g., 'type' => 'pattern').
             // Example (verify with docs): $requestBody['type'] = 'pattern';
        } else {
             // Prepare data for simple text SMS.
             // Verify parameter names like 'message' from Swagger.
             $requestBody['message'] = $message->text;
             // Add the optional 'time' parameter if set in the message.
             if ($message->time) {
                 // Format the time according to the API's required format (e.g., ISO 8601).
                 // Assuming ISO 8601 format "YYYY-MM-DDTHH:mm:ss.sssZ" based on Swagger example.
                 if ($message->time instanceof DateTimeInterface) {
                     $requestBody['time'] = $message->time->format(DateTimeInterface::ATOM); // ISO 8601
                 } else {
                     $requestBody['time'] = $message->time; // Assume it's already in the correct string format
                 }
             }
             // Add any other parameters required by IPPanel's simple text API.
             // Example (verify with docs): $requestBody['type'] = 'text';
        }

        try {
            // Make the HTTP request to IPPanel API.
            // Verify the required headers (especially Authorization) from the Swagger documentation.
            $response = Http::withHeaders([
                // Add headers required by IPPanel, e.g., Authorization header with API Key.
                // This header structure MUST match the Swagger documentation.
                'Authorization' => 'ApiKey ' . $apiKey, // Common example, VERIFY THIS.
                'Content-Type' => 'application/json', // Or 'application/x-www-form-urlencoded', VERIFY THIS.
                'Accept' => 'application/json', // Usually for expecting JSON response.
            ])->post($apiEndpoint, $requestBody);

            // Check the response received from the API.
            // Verify the success criteria and error response structure from the Swagger documentation.
            if ($response->successful()) {
                // SMS sent successfully. You can log the response or handle its data if needed.
                // Based on Swagger, check for status 'OK' and code 200.
                $responseData = $response->json();
                if (isset($responseData['status']) && $responseData['status'] === 'OK' && isset($responseData['code']) && $responseData['code'] === 200) {
                     Log::info('IPPanel SMS sent successfully.', ['response' => $responseData]);
                } else {
                     // API returned 200 but with a non-OK status or non-200 code in the body.
                     Log::error('IPPanel API reported non-OK status or non-200 code.', ['response' => $responseData]);
                     // Optionally throw an exception
                     // throw new Exception('IPPanel API reported non-OK status or non-200 code: ' . json_encode($responseData));
                }
            } else {
                // SMS sending failed due to HTTP status code (e.g., 4xx or 5xx).
                // Log the error details.
                Log::error('IPPanel SMS sending failed due to HTTP status.', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'request_body' => $requestBody, // Log request body for debugging.
                ]);
                // Optionally throw an exception if you want the notification sending process to fail.
                // throw new Exception('IPPanel SMS sending failed with status ' . $response->status() . ': ' . $response->body());
            }

        } catch (Exception $e) {
            // Handle network errors or other exceptions during the HTTP request.
            Log::error('IPPanel SMS sending exception.', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
             // Optionally re-throw the exception.
            // throw $e;
        }
    }
}
