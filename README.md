# ارسال خودکار پست‌های وردپرس به تلگرام

این کد به صورت خودکار پست‌های منتشرشده را به کانال تلگرام ارسال می‌کند. همچنین امکان ارسال مجدد پست به تلگرام از طریق دکمه در ویرایشگر وردپرس وجود دارد.

## نصب و راه‌اندازی

۱. کد را در فایل `functions.php` قالب خود قرار دهید یا آن را به‌صورت افزونه وردپرس استفاده کنید.
۲. توکن ربات تلگرام را در متغیر `$bot_token` جایگزین کنید.
۳. نام کاربری کانال تلگرام خود را در آرایه `$channels` تنظیم کنید.

---

## نحوه تغییر نوع پست

به‌صورت پیش‌فرض این کد برای ارسال پست تایپ سفارشی `nias_requests` تنظیم شده است. اگر می‌خواهید برای یک پست تایپ دیگر استفاده کنید، مقدار `'publish_nias_requests'` را در خط زیر تغییر دهید:

```php
add_action('publish_nias_requests', 'send_post_to_telegram');
```

به‌عنوان مثال، اگر می‌خواهید برای `post` (پست‌های عادی وردپرس) اعمال شود:

```php
add_action('publish_post', 'send_post_to_telegram');
```

---

## نحوه کارکرد

۱. هنگام انتشار یک پست جدید، بررسی می‌شود که آیا قبلاً ارسال شده است یا خیر.
۲. عنوان و لینک پست دریافت می‌شود.
۳. اگر تصویر شاخص وجود داشته باشد، به فرمت `base64` تبدیل می‌شود.
۴. اطلاعات از طریق `wp_remote_post` به API تلگرام ارسال می‌شود.
۵. در صورت موفقیت، متای `telegram_posted` در پست ذخیره می‌شود تا دوباره ارسال نشود.

---

## افزودن وضعیت ارسال به صفحه ویرایش پست

یک متاباکس به صفحه ویرایش پست اضافه می‌شود که وضعیت ارسال به تلگرام را نمایش می‌دهد. اگر پست ارسال شده باشد، پیام «این پست در تلگرام منتشر شده است.» نمایش داده می‌شود.

---

## افزودن دکمه ارسال مجدد

این دکمه در بخش انتشار وردپرس ظاهر شده و با کلیک روی آن، درخواست AJAX به سرور ارسال شده و پست مجدداً به تلگرام ارسال می‌شود.

مثال:

```php
<button type="button" class="button" onclick="resendToTelegram(<?php echo $post->ID; ?>)">
    ارسال مجدد به تلگرام
</button>
```

---

## امنیت

- قبل از ارسال درخواست AJAX، از نانس (`nonce`) استفاده شده تا درخواست‌های غیرمجاز جلوگیری شوند.
- بررسی می‌شود که پست واقعاً منتشر شده باشد تا از ارسال پست‌های پیش‌نویس جلوگیری شود.

---

## مثال تغییر کانال تلگرام

برای تغییر کانال تلگرام، مقدار داخل `$channels` را تغییر دهید:

```php
$channels = array('@my_new_channel');
```

