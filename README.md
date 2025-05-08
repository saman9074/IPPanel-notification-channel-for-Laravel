# Laravel IPPanel Notification Channel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/your-vendor-name/laravel-ippanel-notification-channel.svg?style=flat-square)](https://packagist.org/packages/your-vendor-name/laravel-ippanel-notification-channel)
[![Total Downloads](https://img.shields.io/packagist/dl/your-vendor-name/laravel-ippanel-notification-channel.svg?style=flat-square)](https://packagist.org/packages/your-vendor-name/laravel-ippanel-notification-channel)
[![Build Status](https://img.shields.io/github/actions/workflow/status/your-vendor-name/laravel-ippanel-notification-channel/run-tests.yml?branch=main&style=flat-square)](https://github.com/your-vendor-name/laravel-ippanel-notification-channel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![StyleCI](https://styleci.io/repos/YOUR_REPO_ID/shield?branch=main)](https://styleci.io/repos/YOUR_REPO_ID)
[![License](https://img.shields.io/github/license/your-vendor-name/laravel-ippanel-notification-channel.svg?style=flat-square)](https://github.com/your-vendor-name/laravel-ippanel-notification-channel/blob/main/LICENSE)

This package makes it easy to send notifications using [IPPanel SMS service](https://ippanel.com/) with Laravel.

## Contents

* [Installation](#installation)
* [Configuration](#configuration)
* [Usage](#usage)
    * [Sending a Simple Text Message](#sending-a-simple-text-message)
    * [Sending a Pattern-Based Message](#sending-a-pattern-based-message)
    * [Customizing the Sender](#customizing-the-sender)
    * [Scheduling Messages](#scheduling-messages)
* [Handling Errors](#handling-errors)
* [Testing](#testing)
* [Contributing](#contributing)
* [License](#license)

## Installation

You can install the package via composer:

```bash
composer require your-vendor-name/laravel-ippanel-notification-channel
```

## Configuration

You can publish the config file with:

```bash
php artisan vendor:publish --tag=ippanel-config
```

This will publish the ippanel.php config file to your config directory.

Add your IPPanel API Key and Sender Number to your .env file:

```bash
IPPANEL_API_KEY=your_ippanel_api_key_here
IPPANEL_SENDER_NUMBER=your_default_sender_number_here
# Optional: Override the default API endpoint if necessary
# IPPANEL_API_ENDPOINT=https://api.ippanel.com/v1
```

Make sure to replace your_ippanel_api_key_here and your_default_sender_number_here with your actual IPPanel credentials.


## Usage

To use the IPPanel notification channel, add the IppanelChannel::class to the via() method of your notification class.

```bash
use Illuminate\Notifications\Notification;
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
```
Your notifiable model (e.g., User) needs to implement a routeNotificationForIppanel() method which should return the recipient's phone number(s).

```bash
use Illuminate\Notifications\Notifiable;

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
```

Sending a Simple Text Message

In your notification's toIppanel() method, return an IppanelMessage instance with the message text:

```bash
use NotificationChannels\Ippanel\IppanelMessage;

public function toIppanel($notifiable)
{
    return (new IppanelMessage())
                ->text('Your order has been shipped!');
}
```
Then, send the notification from your notifiable model:

```bash
$user->notify(new OrderShippedNotification());
```
Sending a Pattern-Based Message

If you are using IPPanel's pattern-based SMS (often required for service messages), use the pattern() and variables() methods:

```bash
use NotificationChannels\Ippanel\IppanelMessage;

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
```
Replace your_pattern_code with the code from your IPPanel panel and match the keys in the $variables array to the variable names defined in your pattern.
Customizing the Sender

By default, the channel uses the IPPANEL_SENDER_NUMBER from your config. You can override this for a specific message using the from() method on the IppanelMessage:

```bash
use NotificationChannels\Ippanel\IppanelMessage;

public function toIppanel($notifiable)
{
    return (new IppanelMessage())
                ->text('Message from a custom sender.')
                ->from('your_custom_sender_number');
}
```
Scheduling Messages

You can schedule a message to be sent in the future using the time() method. Pass a DateTimeInterface instance or a string in a format accepted by the IPPanel API (ISO 8601 is recommended):

```bash
use NotificationChannels\Ippanel\IppanelMessage;
use DateTime;

public function toIppanel($notifiable)
{
    $scheduledTime = new DateTime('+1 hour'); // Example: schedule for 1 hour from now

    return (new IppanelMessage())
                ->text('This message will be sent later.')
                ->time($scheduledTime);
}
```
Handling Errors

The channel will log errors if the IPPanel API returns an unsuccessful HTTP status code or a non-OK status in the response body.

If you want to catch specific exceptions, you can wrap your notify() call in a try...catch block and catch the NotificationChannels\Ippanel\Exceptions\CouldNotSendNotification exception:

```bash
use NotificationChannels\Ippanel\Exceptions\CouldNotSendNotification;
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
```
The CouldNotSendNotification exception provides more details about the error from the IPPanel API response.
Testing

You can run the tests with:

```bash
composer test
```
## Contributing

Please see CONTRIBUTING for details.

## License

The MIT License (MIT). Please see License File for more information.

**Note