Laravel IPPanel Notification ChannelThis package makes it easy to send notifications using IPPanel SMS service with Laravel.ContentsInstallationConfigurationUsageSending a Simple Text MessageSending a Pattern-Based MessageCustomizing the SenderScheduling MessagesHandling ErrorsTestingContributingLicenseInstallationYou can install the package via composer:composer require your-vendor-name/laravel-ippanel-notification-channel
ConfigurationYou can publish the config file with:php artisan vendor:publish --tag=ippanel-config
This will publish the ippanel.php config file to your config directory.Add your IPPanel API Key and Sender Number to your .env file:IPPANEL_API_KEY=your_ippanel_api_key_here
IPPANEL_SENDER_NUMBER=your_default_sender_number_here
# Optional: Override the default API endpoint if necessary
# IPPANEL_API_ENDPOINT=https://api.ippanel.com/v1
Make sure to replace your_ippanel_api_key_here and your_default_sender_number_here with your actual IPPanel credentials.UsageTo use the IPPanel notification channel, add the IppanelChannel::class to the via() method of your notification class.use Illuminate\Notifications\Notification;
use NotificationChannels\Ippanel\IppanelChannel;
use NotificationChannels\Ippanel\IppanelMessage;

class OrderShippedNotification extends Notification
{
    public function via($notifiable)
    {
        return [IppanelChannel::class];
        // Or using the alias if registered in your AppServiceProvider:
        // return ['ippanel'];
    }

    public function toIppanel($notifiable)
    {
        // ... build your IppanelMessage instance
    }
}
Your notifiable model (e.g., User) needs to implement a routeNotificationForIppanel() method which should return the recipient's phone number(s).use Illuminate\Notifications\Notifiable;

class User extends Model
{
    use Notifiable;

    public function routeNotificationForIppanel($channel)
    {
        return $this->phone_number; // Assuming 'phone_number' is a column in your users table
        // Or return an array of numbers if sending to multiple recipients:
        // return [$this->phone_number, $this->secondary_phone];
    }
}
Sending a Simple Text MessageIn your notification's toIppanel() method, return an IppanelMessage instance with the message text:use NotificationChannels\Ippanel\IppanelMessage;

public function toIppanel($notifiable)
{
    return (new IppanelMessage())
                ->text('Your order has been shipped!');
}
Then, send the notification from your notifiable model:$user->notify(new OrderShippedNotification());
Sending a Pattern-Based MessageIf you are using IPPanel's pattern-based SMS (often required for service messages), use the pattern() and variables() methods:use NotificationChannels\Ippanel\IppanelMessage;

public function toIppanel($notifiable)
{
    $patternCode = 'your_pattern_code'; // Replace with your actual IPPanel pattern code
    $variables = [
        'name' => $notifiable->name, // Match variable names with your IPPanel pattern
        'order_id' => $this->order->id,
    ];

    return (new IppanelMessage())
                ->pattern($patternCode)
                ->variables($variables);
                // Do NOT use ->text() when sending a pattern-based message
}
Replace your_pattern_code with the code from your IPPanel panel and match the keys in the $variables array to the variable names defined in your pattern.Customizing the SenderBy default, the channel uses the IPPANEL_SENDER_NUMBER from your config. You can override this for a specific message using the from() method on the IppanelMessage:use NotificationChannels\Ippanel\IppanelMessage;

public function toIppanel($notifiable)
{
    return (new IppanelMessage())
                ->text('Message from a custom sender.')
                ->from('your_custom_sender_number');
}
Scheduling MessagesYou can schedule a message to be sent in the future using the time() method. Pass a DateTimeInterface instance or a string in a format accepted by the IPPanel API (ISO 8601 is recommended):use NotificationChannels\Ippanel\IppanelMessage;
use DateTime;

public function toIppanel($notifiable)
{
    $scheduledTime = new DateTime('+1 hour'); // Example: schedule for 1 hour from now

    return (new IppanelMessage())
                ->text('This message will be sent later.')
                ->time($scheduledTime);
}
Handling ErrorsThe channel will log errors if the IPPanel API returns an unsuccessful HTTP status code or a non-OK status in the response body.If you want to catch specific exceptions, you can wrap your notify() call in a try...catch block and catch the NotificationChannels\Ippanel\Exceptions\CouldNotSendNotification exception:use NotificationChannels\Ippanel\Exceptions\CouldNotSendNotification;
use App\Notifications\OrderShippedNotification;
use App\Models\User;

$user = User::find(1);

try {
    $user->notify(new OrderShippedNotification());
} catch (CouldNotSendNotification $e) {
    // Handle the error, e.g., log it, notify an admin, etc.
    Log::error('Failed to send IPPanel SMS: ' . $e->getMessage());
    // Or display a user-friendly error message
    // session()->flash('error', 'Failed to send SMS notification.');
}
The CouldNotSendNotification exception provides more details about the error from the IPPanel API response.TestingYou can run the tests with:composer test
ContributingPlease see CONTRIBUTING for details.LicenseThe MIT License (MIT). Please see License File for more information.Note: Replace your-vendor-name with your actual vendor name and laravel-ippanel-notification-channel with your package name in the installation command, Packagist links, and GitHub links. Also, replace YOUR_REPO_ID with your StyleCI repository ID if you use StyleCI.