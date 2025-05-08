<?php

namespace NotificationChannels\Ippanel\Test; // Use the Test namespace defined in composer.json

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log; // Import the Log facade
use NotificationChannels\Ippanel\IppanelChannel;
use NotificationChannels\Ippanel\IppanelMessage;
use Orchestra\Testbench\TestCase; // Use Orchestra\Testbench for testing packages
use DateTime; // Import DateTime for testing time parameter
use DateTimeInterface; // Import DateTimeInterface

class IppanelChannelTest extends TestCase
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        // Register the package's service provider for testing.
        return [\NotificationChannels\Ippanel\IppanelNotificationChannelServiceProvider::class];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Set up IPPanel configuration for testing.
        // These values will be used by the channel class.
        $app['config']->set('ippanel.api_key', 'OWVkYjg3ZjUtY2IzMi00MDFhLTgwNzYtMTU1Yzg3MmYwY2NhOTcxZTA2NTliNjNmOTNlODY4NWNmMTdiOTRhMjg1MzE='); // Using a generic test key here
        // Set a test sender number in the config.
        $app['config']->set('ippanel.sender_number', '3000505');
        // Use the ACTUAL IPPanel base endpoint for testing, as we will mock requests to it.
        $app['config']->set('ippanel.endpoint', 'https://api.ippanel.com/v1');
    }

    /** @test */
    public function it_sends_a_simple_sms_message_using_config_sender()
    {
        // Arrange: Set up the fake HTTP response for a successful simple SMS send.
        // Based on Swagger: 200 OK with status 'OK' and a message_id in data.
        // Mock the request to the ACTUAL endpoint path.
        Http::fake([
            'https://api.ippanel.com/v1/sms/send/webservice/single' => Http::response(['status' => 'OK', 'code' => 200, 'errorMessage' => '', 'data' => ['message_id' => 12345]], 200),
        ]);

        // Create a mock notifiable instance with a phone number.
        $notifiable = new TestNotifiable();
        $notifiable->phone_number = '09158282780';

        // Create a test notification instance with a simple text message (without setting 'from').
        $notification = new TestNotification('Hello from the test!');

        // Act: Send the notification through the IPPanel channel.
        (new IppanelChannel())->send($notifiable, $notification);

        // Assert: Verify that the correct HTTP request was sent to the fake endpoint.
        // Verify that the sender used is the one from the config.
        Http::assertSent(function ($request) use ($notifiable) {
            // Dump the request details to debug why the assertion is failing
            // Uncomment the line below to see the request details
            // dd([
            //     'url' => $request->url(),
            //     'method' => $request->method(),
            //     'headers' => $request->headers(),
            //     'body' => $request->body(), // Raw body
            //     'json' => $request->json(), // Parsed JSON body (if applicable)
            // ]);

            return $request->url() == 'https://api.ippanel.com/v1/sms/send/webservice/single' && // Corrected endpoint
                   $request->method() == 'POST' &&
                   $request['recipient'][0] == $notifiable->phone_number && // Assuming recipient is an array
                   $request['sender'] == config('ippanel.sender_number') && // Assert sender is from config
                   $request['message'] == 'Hello from the test!' && // Simple text message parameter
                   !isset($request['time']) && // Ensure time is NOT sent if not set in message
                   $request->hasHeader('Authorization', 'ApiKey ' . config('ippanel.api_key')) && // Verify Authorization header using config
                   $request->hasHeader('Content-Type', 'application/json') && // Verify Content-Type header
                   $request->hasHeader('Accept', 'application/json'); // Verify Accept header
        });
    }

     /** @test */
    public function it_sends_a_simple_sms_message_with_time_using_config_sender()
    {
        // Arrange: Set up the fake HTTP response for a successful simple SMS send with time.
        // Mock the request to the ACTUAL endpoint path.
        Http::fake([
            'https://api.ippanel.com/v1/sms/send/webservice/single' => Http::response(['status' => 'OK', 'code' => 200, 'errorMessage' => '', 'data' => ['message_id' => 54321]], 200),
        ]);

        $notifiable = new TestNotifiable();
        $notifiable->phone_number = '09123456789';

        // Create a test notification instance with text and time (without setting 'from').
        $scheduledTime = new DateTime('2025-12-31 10:00:00');
        $notification = (new TestNotification('Hello scheduled test!'))->time($scheduledTime);

        // Act: Send the notification.
        (new IppanelChannel())->send($notifiable, $notification);

        // Assert: Verify the request includes the formatted time.
        Http::assertSent(function ($request) use ($notifiable, $scheduledTime) {
             return $request->url() == 'https://api.ippanel.com/v1/sms/send/webservice/single' &&
                    $request->method() == 'POST' &&
                    $request['recipient'][0] == $notifiable->phone_number &&
                    $request['sender'] == config('ippanel.sender_number') && // Assert sender is from config
                    $request['message'] == 'Hello scheduled test!' &&
                    $request['time'] == $scheduledTime->format(DateTimeInterface::ATOM) && // Verify formatted time
                    $request->hasHeader('Authorization', 'ApiKey ' . config('ippanel.api_key')) && // Verify Authorization header using config
                    $request->hasHeader('Content-Type', 'application/json') &&
                    $request->hasHeader('Accept', 'application/json');
        });
    }

    /** @test */
    public function it_sends_a_simple_sms_message_using_message_sender_override()
    {
        // Arrange: Set up the fake HTTP response for a successful simple SMS send.
        Http::fake([
            'https://api.ippanel.com/v1/sms/send/webservice/single' => Http::response(['status' => 'OK', 'code' => 200, 'errorMessage' => '', 'data' => ['message_id' => 98765]], 200),
        ]);

        $notifiable = new TestNotifiable();
        $notifiable->phone_number = '09121112233';

        // Create a test notification instance with text AND setting 'from'.
        $overrideSender = 'test_override_sender';
        $notification = (new TestNotification('Hello with override sender!'))->from($overrideSender);

        // Act: Send the notification.
        (new IppanelChannel())->send($notifiable, $notification);

        // Assert: Verify the request uses the sender from the message object, NOT the config.
        Http::assertSent(function ($request) use ($notifiable, $overrideSender) {
             return $request->url() == 'https://api.ippanel.com/v1/sms/send/webservice/single' &&
                    $request->method() == 'POST' &&
                    $request['recipient'][0] == $notifiable->phone_number &&
                    $request['sender'] == $overrideSender && // Assert sender is from the message object
                    $request['message'] == 'Hello with override sender!' &&
                    !isset($request['time']) &&
                    $request->hasHeader('Authorization', 'ApiKey ' . config('ippanel.api_key')) && // Verify Authorization header using config
                    $request->hasHeader('Content-Type', 'application/json') &&
                    $request->hasHeader('Accept', 'application/json');
        });
    }


    /** @test */
    public function it_sends_a_pattern_based_sms_message_using_config_sender()
    {
         // Arrange: Set up the fake HTTP response for a successful pattern-based SMS send.
         // Verify the expected response structure from IPPanel's Swagger docs for pattern send.
         // Assuming the pattern endpoint is different and has a similar success structure.
         // Mock the request to the ACTUAL endpoint path for patterns.
         Http::fake([
             'https://api.ippanel.com/v1/messages/patterns/send' => Http::response(['status' => 'OK', 'code' => 200, 'errorMessage' => '', 'data' => ['message_id' => 67890]], 200),
         ]);

         // Create a mock notifiable instance with a phone number.
         $notifiable = new TestNotifiable();
         $notifiable->phone_number = '09129876543';

         // Define pattern code and variables (without setting 'from').
         $patternCode = 'test_pattern_code';
         $variables = ['name' => 'Test User', 'code' => '12345'];

         // Create a test notification instance for a pattern-based message.
         $notification = new TestNotification(null, $patternCode, $variables); // Pass null for text

         // Act: Send the notification through the IPPanel channel.
         (new IppanelChannel())->send($notifiable, $notification);

         // Assert: Verify that the correct HTTP request was sent for the pattern message.
         // Verify that the sender used is the one from the config.
         Http::assertSent(function ($request) use ($notifiable, $patternCode, $variables) {
              return $request->url() == 'https://api.ippanel.com/v1/messages/patterns/send' && // Pattern endpoint (verify with docs)
                     $request->method() == 'POST' &&
                     $request['recipient'][0] == $notifiable->phone_number && // Assuming recipient is an array
                     $request['sender'] == config('ippanel.sender_number') && // Assert sender is from config
                     $request['pattern_code'] == $patternCode && // Pattern code parameter
                     $request['variables'] == $variables && // Pattern variables parameter
                     !isset($request['time']) && // Ensure time is NOT sent for pattern messages unless supported by API
                     $request->hasHeader('Authorization', 'ApiKey ' . config('ippanel.api_key')) && // Verify Authorization header using config
                     $request->hasHeader('Content-Type', 'application/json') && // Verify Content-Type header
                     $request->hasHeader('Accept', 'application/json'); // Verify Accept header
         });
    }

     /** @test */
    public function it_sends_a_pattern_based_sms_message_using_message_sender_override()
    {
         // Arrange: Set up the fake HTTP response for a successful pattern-based SMS send.
         Http::fake([
             'https://api.ippanel.com/v1/messages/patterns/send' => Http::response(['status' => 'OK', 'code' => 200, 'errorMessage' => '', 'data' => ['message_id' => 54321]], 200),
         ]);

         $notifiable = new TestNotifiable();
         $notifiable->phone_number = '09124445566';

         $patternCode = 'test_pattern_code';
         $variables = ['name' => 'Another User'];
         $overrideSender = 'test_override_sender_pattern';

         // Create a test notification with pattern details AND setting 'from'.
         $notification = (new TestNotification(null, $patternCode, $variables))->from($overrideSender);

         // Act: Send the notification.
         (new IppanelChannel())->send($notifiable, $notification);

         // Assert: Verify the request uses the sender from the message object.
         Http::assertSent(function ($request) use ($notifiable, $patternCode, $variables, $overrideSender) {
              return $request->url() == 'https://api.ippanel.com/v1/messages/patterns/send' &&
                     $request->method() == 'POST' &&
                     $request['recipient'][0] == $notifiable->phone_number &&
                     $request['sender'] == $overrideSender && // Assert sender is from the message object
                     $request['pattern_code'] == $patternCode &&
                     $request['variables'] == $variables &&
                     !isset($request['time']) &&
                     $request->hasHeader('Authorization', 'ApiKey ' . config('ippanel.api_key')) && // Verify Authorization header using config
                     $request->hasHeader('Content-Type', 'application/json') &&
                     $request->hasHeader('Accept', 'application/json');
         });
    }


    /** @test */
    public function it_logs_an_error_on_unsuccessful_api_response()
    {
        // Arrange: Set up the fake HTTP response for an unsuccessful API call (e.g., 400 Bad Request).
        // Based on Swagger: 400 Bad Request with status 'Bad request.' etc.
         // Mock the request to the ACTUAL endpoint path.
        Http::fake([
            'https://api.ippanel.com/v1/sms/send/webservice/single' => Http::response(['status' => 'Bad request.', 'code' => 400, 'errorMessage' => 'Invalid API Key', 'data' => null], 400), // Corrected endpoint and response structure
        ]);

        // Mock the Log facade to assert that an error is logged.
        Log::shouldReceive('error')
            ->once()
            ->with('IPPanel SMS sending failed due to HTTP status.', \Mockery::any()); // Check if error is logged with specific message

        $notifiable = new TestNotifiable();
        $notifiable->phone_number = '09123456789';

        $notification = new TestNotification('This message should fail.');

        // Act: Send the notification.
        (new IppanelChannel())->send($notifiable, $notification);

        // Assert: Verify that the HTTP request was sent (even though it failed)
        Http::assertSent(function ($request) {
            return $request->url() == 'https://api.ippanel.com/v1/sms/send/webservice/single'; // Corrected endpoint
        });
    }

    /** @test */
    public function it_skips_sending_if_no_recipient_is_found()
    {
        // Arrange: Create a notifiable instance without a phone number.
        $notifiable = new TestNotifiable();
        // $notifiable->phone_number is null or empty

        // Mock the Log facade to assert that an info message is logged for skipping.
        Log::shouldReceive('info')
            ->once()
            ->with('IPPanel Notification skipped: No recipient found for notifiable.');

        // Ensure no HTTP request is made when skipping.
        Http::fake(); // Start faking HTTP requests
        Http::assertNothingSent(); // Assert that nothing was sent

        $notification = new TestNotification('This message should be skipped.');

        // Act: Send the notification.
        (new IppanelChannel())->send($notifiable, $notification);

        // Assert: The assertions on Log and Http::assertNothingSent cover this case.
    }


    // Add more tests for other scenarios, such as:
    // - Sending to multiple recipients.
    // - Handling different API error responses based on IPPanel docs.
    // - Testing API responses with status OK but non-200 code or non-OK status in body.
}

// --- Helper classes for testing ---

// A simple Notifiable class for testing purposes.
class TestNotifiable
{
    use \Illuminate\Notifications\Notifiable;

    public $phone_number; // Property to hold the phone number

    /**
     * Route notifications for the IPPanel channel.
     *
     * @param  mixed  $channel
     * @return string|array|null
     */
    public function routeNotificationForIppanel($channel)
    {
        // Return the phone number property.
        return $this->phone_number;
    }
}

// A simple Notification class for testing purposes.
class TestNotification extends Notification
{
    protected $messageText;
    protected $patternCode;
    protected $variables;
    protected $time; // Add time property
    protected $from; // Add from property

    /**
     * Create a new notification instance.
     *
     * @param string|null $messageText
     * @param string|null $patternCode
     * @param array $variables
     */
     public function __construct($messageText = null, $patternCode = null, array $variables = [])
    {
        $this->messageText = $messageText;
        $this->patternCode = $patternCode;
        $this->variables = $variables;
    }

    /**
     * Set the time for scheduling the message.
     *
     * @param DateTimeInterface|string $time The time to send the message.
     * @return $this
     */
    public function time($time)
    {
        $this->time = $time;
        return $this;
    }

    /**
     * Set the sender number for this specific message, overriding the config default.
     *
     * @param string $from The sender number.
     * @return $this
     */
    public function from($from)
    {
        $this->from = $from;
        return $this;
    }


    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        // Specify the IPPanel channel.
        return [\NotificationChannels\Ippanel\IppanelChannel::class];
    }

    /**
     * Get the IPPanel / SMS representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \NotificationChannels\Ippanel\IppanelMessage
     */
    public function toIppanel($notifiable)
    {
        $message = (new IppanelMessage());

        if ($this->patternCode) {
            $message->pattern($this->patternCode)
                    ->variables($this->variables);
        } else {
            $message->text($this->messageText);
        }

        // Set the time on the message if it was set on the notification
        if ($this->time) {
            $message->time($this->time);
        }

        // Set the sender on the message if it was set on the notification
        if ($this->from) {
             $message->from($this->from);
        }


        return $message;
    }
}
