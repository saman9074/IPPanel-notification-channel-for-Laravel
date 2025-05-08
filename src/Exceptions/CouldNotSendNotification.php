<?php

// Package namespace for exceptions.
namespace NotificationChannels\Ippanel\Exceptions;

use Exception; // Import the base Exception class
use Psr\Http\Message\ResponseInterface; // Import ResponseInterface if you need type hinting for the response object

class CouldNotSendNotification extends Exception
{
    /**
     * Thrown when the IPPanel service responds with an error.
     *
     * @param \Illuminate\Http\Client\Response|\Psr\Http\Message\ResponseInterface $response The HTTP response from the IPPanel API.
     * @return static
     */
    public static function serviceRespondedWithAnError($response)
    {
        // Attempt to get more details from the response body if it's JSON.
        $statusCode = $response->status();
        $errorBody = $response->body();
        $errorMessage = "IPPanel service responded with status code {$statusCode}.";

        try {
            $responseData = $response->json();
            if (isset($responseData['errorMessage'])) {
                // Assuming IPPanel returns an 'errorMessage' field in case of errors.
                // Adjust key based on actual Swagger documentation.
                $errorMessage .= " Error: " . json_encode($responseData['errorMessage']);
            } elseif (isset($responseData['message'])) {
                 // Sometimes APIs use a generic 'message' field for errors.
                 $errorMessage .= " Message: " . json_encode($responseData['message']);
            }
        } catch (\Exception $e) {
            // If response body is not JSON or parsing fails, just use the status code and body.
            $errorMessage .= " Response body: {$errorBody}";
        }


        return new static($errorMessage);
    }

    // You can add other static factory methods for different types of errors,
    // e.g., network errors, configuration errors, etc.

    /**
     * Thrown when the API key is missing or invalid in the configuration.
     *
     * @return static
     */
    public static function apiKeyMissing()
    {
        return new static("IPPanel API Key is missing or not configured.");
    }

    /**
     * Thrown when the sender number is missing or invalid in the configuration.
     *
     * @return static
     */
    public static function senderNumberMissing()
    {
         return new static("IPPanel Sender Number is missing or not configured.");
    }

    /**
     * Thrown when the notifiable model does not have a routeNotificationForIppanel method.
     *
     * @param string $modelClass The class name of the notifiable model.
     * @return static
     */
    public static function invalidNotifiable($modelClass)
    {
        return new static("Notifiable model {$modelClass} does not have a routeNotificationForIppanel method.");
    }
}
