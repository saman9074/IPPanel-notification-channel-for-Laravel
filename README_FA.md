# کانال نوتیفیکیشن IPPanel برای لاراول

[![نسخه آخر در Packagist](https://img.shields.io/packagist/v/your-vendor-name/laravel-ippanel-notification-channel.svg?style=flat-square)](https://packagist.org/packages/your-vendor-name/laravel-ippanel-notification-channel)
[![تعداد دانلودها](https://img.shields.io/packagist/dl/your-vendor-name/laravel-ippanel-notification-channel.svg?style=flat-square)](https://packagist.org/packages/your-vendor-name/laravel-ippanel-notification-channel)
[![وضعیت ساخت](https://img.shields.io/github/actions/workflow/status/your-vendor-name/laravel-ippanel-notification-channel/run-tests.yml?branch=main&style=flat-square)](https://github.com/your-vendor-name/laravel-ippanel-notification-channel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![StyleCI](https://styleci.io/repos/YOUR_REPO_ID/shield?branch=main)](https://styleci.io/repos/YOUR_REPO_ID)
[![لایسنس](https://img.shields.io/github/license/your-vendor-name/laravel-ippanel-notification-channel.svg?style=flat-square)](https://github.com/your-vendor-name/laravel-ippanel-notification-channel/blob/main/LICENSE)

این پکیج به شما امکان می‌دهد تا از سرویس پیامک [IPPanel](https://ippanel.com/) در لاراول برای ارسال نوتیفیکیشن استفاده کنید.

## فهرست

- [نصب](#نصب)
- [پیکربندی](#پیکربندی)
- [نحوه استفاده](#نحوه-استفاده)
  - [ارسال پیام متنی ساده](#ارسال-پیام-متنی-ساده)
  - [ارسال پیام با الگو (Pattern)](#ارسال-پیام-با-الگو-pattern)
  - [سفارشی‌سازی شماره ارسال‌کننده](#سفارشی‌سازی-شماره-ارسال‌کننده)
  - [زمان‌بندی پیام‌ها](#زمان‌بندی-پیام‌ها)
- [مدیریت خطاها](#مدیریت-خطاها)
- [تست](#تست)
- [مشارکت در توسعه](#مشارکت-در-توسعه)
- [لایسنس](#لایسنس)

## نصب

> **نکته مهم:** از آن‌جا که پکیج هنوز در Packagist منتشر نشده، لطفاً از روش دوم استفاده کنید.

### ۱- نصب از طریق Composer (پس از انتشار در Packagist)

```bash
composer require your-vendor-name/laravel-ippanel-notification-channel
```

### ۲- نصب مستقیم از GitHub (برای توسعه یا قبل از انتشار)

ابتدا در فایل `composer.json` پروژه خود، بخش `repositories` را اضافه کنید:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/saman9074/IPPanel-notification-channel-for-Laravel"
    }
],
```

سپس پکیج را نصب کنید:

```bash
composer require saman9074/ippanel-notification-channel-for-laravel
```

## پیکربندی

برای انتشار فایل پیکربندی:

```bash
php artisan vendor:publish --tag=ippanel-config
```

این دستور فایل `ippanel.php` را در مسیر `config/` پروژه شما قرار می‌دهد.

مقادیر زیر را به فایل `.env` پروژه خود اضافه کنید:

```env
IPPANEL_API_KEY=your_ippanel_api_key_here
IPPANEL_SENDER_NUMBER=your_default_sender_number_here
# اختیاری: تغییر آدرس API
# IPPANEL_API_ENDPOINT=https://api.ippanel.com/v1
```

مقدار `IPPANEL_API_KEY` و `IPPANEL_SENDER_NUMBER` را با اطلاعات واقعی خود جایگزین کنید.

## نحوه استفاده

برای استفاده از کانال IPPanel، کافی است کلاس `IppanelChannel::class` را در متد `via` نوتیفیکیشن خود قرار دهید:

```php
use Illuminate\Notifications\Notification;
use NotificationChannels\Ippanel\IppanelChannel;
use NotificationChannels\Ippanel\IppanelMessage;

class OrderShippedNotification extends Notification
{
    public function via($notifiable)
    {
        return [IppanelChannel::class];
        // یا استفاده از alias ثبت‌شده:
        // return ['ippanel'];
    }

    public function toIppanel($notifiable)
    {
        // ایجاد پیام
    }
}
```

در مدل قابل نوتیفای (مثلاً User)، متدی با نام `routeNotificationForIppanel` تعریف کنید:

```php
use Illuminate\Notifications\Notifiable;

class User extends Model
{
    use Notifiable;

    public function routeNotificationForIppanel($channel)
    {
        return $this->phone_number;
        // یا برای ارسال به چند شماره:
        // return [$this->phone_number, $this->backup_phone];
    }
}
```

### ارسال پیام متنی ساده

```php
public function toIppanel($notifiable)
{
    return (new IppanelMessage())
        ->text('سفارش شما ارسال شد.');
}
```

### ارسال پیام با الگو (Pattern)

```php
public function toIppanel($notifiable)
{
    return (new IppanelMessage())
        ->pattern('pattern_code_here')
        ->variables([
            'name' => $notifiable->name,
            'order_id' => $this->order->id,
        ]);
}
```

### سفارشی‌سازی شماره ارسال‌کننده

```php
public function toIppanel($notifiable)
{
    return (new IppanelMessage())
        ->text('پیامی با فرستنده سفارشی.')
        ->from('3000XXXXXX');
}
```

### زمان‌بندی پیام‌ها

```php
use DateTime;

public function toIppanel($notifiable)
{
    $time = new DateTime('+1 hour');

    return (new IppanelMessage())
        ->text('این پیام با تأخیر ارسال می‌شود.')
        ->time($time);
}
```

## مدیریت خطاها

```php
use NotificationChannels\Ippanel\Exceptions\CouldNotSendNotification;

try {
    $user->notify(new OrderShippedNotification());
} catch (CouldNotSendNotification $e) {
    Log::error('خطا در ارسال پیامک IPPanel: ' . $e->getMessage());
}
```

## تست

```bash
composer test
```

## مشارکت در توسعه

لطفاً فایل CONTRIBUTING را مشاهده کنید.

## لایسنس

این پروژه تحت مجوز MIT منتشر شده است.