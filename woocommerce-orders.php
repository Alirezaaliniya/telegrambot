<?php
/**
 * ارسال خودکار اطلاعات سفارش‌های ووکامرس به تلگرام
 * 
 * این کد اطلاعات سفارش‌های ووکامرس را به کانال تلگرام ارسال می‌کند.
 * شامل: نام مشتری، آدرس، کد پستی، شماره تماس، محصولات سفارش،
 * مبلغ کل، روش پرداخت، شماره تراکنش و روش ارسال
 */

// هوک برای ارسال سفارش به تلگرام پس از ثبت سفارش
add_action('woocommerce_new_order', 'send_wc_order_to_telegram');
add_action('woocommerce_order_status_changed', 'send_wc_order_status_to_telegram', 10, 3);

/**
 * ارسال اطلاعات سفارش به تلگرام
 */
function send_wc_order_to_telegram($order_id) {
    // بررسی وجود ووکامرس
    if (!function_exists('wc_get_order')) {
        return;
    }

    // دریافت اطلاعات سفارش
    $order = wc_get_order($order_id);
    if (!$order) return;

    // بررسی اینکه قبلاً ارسال نشده باشد
    if (get_post_meta($order_id, 'telegram_order_posted', true)) {
        return;
    }

    // ارسال اطلاعات به تلگرام
    send_telegram_order_notification($order);
}

/**
 * ارسال وضعیت جدید سفارش به تلگرام
 */
function send_wc_order_status_to_telegram($order_id, $old_status, $new_status) {
    // بررسی وجود ووکامرس
    if (!function_exists('wc_get_order')) {
        return;
    }

    // دریافت اطلاعات سفارش
    $order = wc_get_order($order_id);
    if (!$order) return;

    // ارسال اطلاعات به تلگرام با مشخص کردن تغییر وضعیت
    $status_changed = true;
    send_telegram_order_notification($order, $status_changed, $old_status, $new_status);
}

/**
 * تابع اصلی ارسال اطلاعات سفارش به تلگرام
 */
function send_telegram_order_notification($order, $status_changed = false, $old_status = '', $new_status = '') {
    // توکن بات تلگرام - این مقدار را با توکن بات خود جایگزین کنید
    $bot_token = 'YOUR_BOT_TOKEN_HERE'; 
    
    // آیدی کانال تلگرام - این مقدار را با آیدی کانال خود جایگزین کنید
    $channel = '@yourchannel';
    
    // اگر توکن تنظیم نشده است، خارج شویم
    if (!$bot_token || $bot_token === 'YOUR_BOT_TOKEN_HERE') return;

    // برای هاست های داخل ایران لینک زیر را با توجه به توضیحات ریپازیتوری به لینک کلودفلر تغییر دهید
    // https://github.com/soheylfarzane/TelegramByapss
    $api_url = 'https://api.telegram.org/bot' . $bot_token;
    
    // دریافت اطلاعات سفارش
    $order_id = $order->get_id();
    $order_number = $order->get_order_number();
    $order_date = $order->get_date_created()->date_i18n('Y-m-d H:i:s');
    $order_status = wc_get_order_status_name($order->get_status());
    $payment_method = $order->get_payment_method_title();
    $shipping_method = $order->get_shipping_method();
    $transaction_id = $order->get_transaction_id();
    $total = $order->get_total();
    
    // اطلاعات مشتری
    $first_name = $order->get_billing_first_name();
    $last_name = $order->get_billing_last_name();
    $customer_name = $first_name . ' ' . $last_name;
    $phone = $order->get_billing_phone();
    
    // آدرس
    $state = $order->get_billing_state();
    $city = $order->get_billing_city();
    $address = $order->get_billing_address_1() . ' ' . $order->get_billing_address_2();
    $postcode = $order->get_billing_postcode();
    
    // تبدیل کد استان به نام استان (اختیاری)
    $states = WC()->countries->get_states('IR');
    $state_name = isset($states[$state]) ? $states[$state] : $state;
    
    // لیست محصولات
    $items_text = "📋 *محصولات سفارش:*\n";
    foreach ($order->get_items() as $item) {
        $product_name = $item->get_name();
        $product_qty = $item->get_quantity();
        $product_total = $item->get_total();
        $items_text .= "• $product_name × $product_qty - " . number_format($product_total) . " تومان\n";
    }
    
    // متن پیام
    if ($status_changed) {
        $title = "🔄 *تغییر وضعیت سفارش #$order_number*";
        $status_text = "\n*وضعیت قبلی:* " . wc_get_order_status_name($old_status) . 
                      "\n*وضعیت جدید:* " . wc_get_order_status_name($new_status);
    } else {
        $title = "🛒 *سفارش جدید #$order_number*";
        $status_text = "\n*وضعیت سفارش:* $order_status";
    }
    
    $message = "$title\n\n" .
               "📅 *تاریخ:* $order_date" . 
               $status_text . 
               "\n\n👤 *اطلاعات مشتری:*" . 
               "\n*نام و نام خانوادگی:* $customer_name" . 
               "\n*تلفن:* $phone" . 
               "\n*استان:* $state_name" . 
               "\n*شهر:* $city" . 
               "\n*آدرس:* $address" . 
               "\n*کد پستی:* $postcode" . 
               "\n\n💰 *اطلاعات پرداخت:*" . 
               "\n*مبلغ کل:* " . number_format($total) . " تومان" . 
               "\n*روش پرداخت:* $payment_method" . 
               ($transaction_id ? "\n*شماره تراکنش:* $transaction_id" : "") . 
               "\n*روش ارسال:* $shipping_method" .
               "\n\n$items_text";
    
    // ارسال پیام به تلگرام
    $telegram_api_url = $api_url . '/sendMessage';
    $body = array(
        'chat_id' => $channel,
        'text' => $message,
        'parse_mode' => 'Markdown'
    );

    $args = array(
        'timeout' => 30,
        'headers' => array('Content-Type' => 'application/json'),
        'body' => json_encode($body)
    );
    
    $response = wp_remote_post($telegram_api_url, $args);
    
    if (!is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body);
        if ($result && $result->ok) {
            // اگر سفارش جدید است، وضعیت ارسال را ذخیره کنیم
            if (!$status_changed) {
                update_post_meta($order_id, 'telegram_order_posted', true);
            }
            return true;
        }
    }
    
    return false;
}

/**
 * افزودن متاباکس وضعیت ارسال به تلگرام در صفحه سفارش
 */
function add_telegram_order_status_meta_box() {
    add_meta_box(
        'telegram_order_status',
        'وضعیت ارسال به تلگرام',
        'telegram_order_status_meta_box_callback',
        'shop_order',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'add_telegram_order_status_meta_box');

function telegram_order_status_meta_box_callback($post) {
    $posted = get_post_meta($post->ID, 'telegram_order_posted', true);
    echo $posted ? 'این سفارش در تلگرام ارسال شده است.' : 'این سفارش هنوز در تلگرام ارسال نشده است.';
    
    // دکمه ارسال مجدد
    echo '<p><button type="button" class="button" id="resend_telegram_order" data-order="' . $post->ID . '">ارسال مجدد به تلگرام</button></p>';
    echo '<div id="telegram-order-resend-status"></div>';
    
    // اسکریپت ارسال مجدد
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#resend_telegram_order').on('click', function() {
                var status = $('#telegram-order-resend-status');
                var orderId = $(this).data('order');
                
                status.text('در حال ارسال...');
                
                $.post(ajaxurl, {
                    action: 'resend_order_to_telegram',
                    order_id: orderId,
                    nonce: '<?php echo wp_create_nonce('resend_order_to_telegram'); ?>'
                }, function(response) {
                    if(response.success) {
                        status.text('با موفقیت ارسال شد!');
                    } else {
                        status.text('خطا در ارسال: ' + response.data);
                    }
                });
            });
        });
    </script>
    <?php
}

/**
 * پردازش درخواست AJAX برای ارسال مجدد سفارش به تلگرام
 */
function handle_resend_order_to_telegram() {
    check_ajax_referer('resend_order_to_telegram', 'nonce');
    
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    if (!$order_id) {
        wp_send_json_error('شناسه سفارش نامعتبر است.');
    }
    
    if (!function_exists('wc_get_order')) {
        wp_send_json_error('ووکامرس فعال نیست.');
    }
    
    $order = wc_get_order($order_id);
    if (!$order) {
        wp_send_json_error('سفارش یافت نشد.');
    }
    
    // حذف متا برای امکان ارسال مجدد
    delete_post_meta($order_id, 'telegram_order_posted');
    
    // ارسال مجدد
    $result = send_telegram_order_notification($order);
    
    if ($result) {
        wp_send_json_success();
    } else {
        wp_send_json_error('خطا در ارسال به تلگرام.');
    }
}
add_action('wp_ajax_resend_order_to_telegram', 'handle_resend_order_to_telegram');
