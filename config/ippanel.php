<?php

return [
    /**
     * IPPanel API Key.
     * You can find this in your IPPanel user account settings.
     * This will be read from your .env file in the Laravel project.
     */
    'api_key' => env('IPPANEL_API_KEY'),

    /**
     * Default Sender Number.
     * This is the default number that SMS messages will be sent from.
     * This will be read from your .env file in the Laravel project.
     */
    'sender_number' => env('IPPANEL_SENDER_NUMBER'),

    /**
     * IPPanel API Endpoint.
     * The base URL for the IPPanel API.
     * Make sure this matches the documentation provided by IPPanel.
     * You can override the default value in your .env file.
     */
    'endpoint' => env('IPPANEL_API_ENDPOINT', 'https://api.ippanel.com/v1/messages'), // Replace with the actual endpoint if different
];
// This configuration file is used to set up the IPPanel notification channel.