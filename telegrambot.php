<?php
/**
 * ارسال خودکار پست‌های منتشر شده در وردپرس به تلگرام
 */
function send_post_to_telegram($post_id) {
    // جلوگیری از اجرای تابع در هنگام ذخیره خودکار
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) 
        return;

    // ارسال فقط برای پست‌های منتشر شده
    if (get_post_status($post_id) != 'publish')
        return;

    // جلوگیری از ارسال مجدد
    if (get_post_meta($post_id, 'telegram_posted', true))
        return;

    $post = get_post($post_id);
    $post_title = $post->post_title;
    $post_url = get_permalink($post_id);

    // دریافت تصویر شاخص و تبدیل به Base64
    $image_base64 = '';
    if (has_post_thumbnail($post_id)) {
        $thumbnail_id = get_post_thumbnail_id($post_id);
        $image_path = get_attached_file($thumbnail_id);
        
        if ($image_path && file_exists($image_path)) {
            $image_data = file_get_contents($image_path);
            if ($image_data !== false) {
                $image_base64 = base64_encode($image_data);
            }
        }
    }

    // مقدار توکن مربوط به ربات را وارد کنید
        $bot_token = 'YOUR_BOT_TOKEN_HERE';

  //برای هاست های داخل ایران لینک زیر را با توجه به توضیحات ریپازیتوری پایین به لینک کلودفلر تغییر بدید 
  //https://github.com/soheylfarzane/TelegramByapss
    $api_url = 'https://api.telegram.org/bot' . $bot_token;
    
    // لیست کانال‌ها را تنظیم کنید
    $channels = array('@yourchannel');

    foreach ($channels as $channel) {
        $caption = "📝 " . $post_title . "\n\n🔗 مشاهده مطلب کامل:\n" . $post_url;

        $args = array(
            'timeout' => 30,
            'headers' => array('Content-Type' => 'application/json')
        );

        if ($image_base64) {
            $telegram_api_url = $api_url . '/sendPhoto';
            $body = array(
                'chat_id' => $channel,
                'photo' => 'data:image/jpeg;base64,' . $image_base64,
                'caption' => $caption,
                'parse_mode' => 'HTML'
            );
        } else {
            $telegram_api_url = $api_url . '/sendMessage';
            $body = array(
                'chat_id' => $channel,
                'text' => $caption,
                'parse_mode' => 'HTML'
            );
        }

        $args['body'] = json_encode($body);
        $response = wp_remote_post($telegram_api_url, $args);

        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $result = json_decode($body);
            if ($result && $result->ok) {
                update_post_meta($post_id, 'telegram_posted', true);
                return true;
            }
        }
    }
}

// تغییر این مقدار برای نوع پست دلخواه
add_action('publish_nias_requests', 'send_post_to_telegram');

/**
 * نمایش وضعیت ارسال به تلگرام در متاباکس
 */
function add_telegram_status_meta_box() {
    add_meta_box('telegram_status', 'وضعیت ارسال به تلگرام', 'telegram_status_meta_box_callback', 'nias_requests', 'side');
}
add_action('add_meta_boxes', 'add_telegram_status_meta_box');

function telegram_status_meta_box_callback($post) {
    $posted = get_post_meta($post->ID, 'telegram_posted', true);
    echo $posted ? 'این پست در تلگرام منتشر شده است.' : 'این پست هنوز در تلگرام منتشر نشده است.';
}

/**
 * افزودن دکمه ارسال مجدد در صفحه ویرایش پست
 */
function add_resend_telegram_button($post) {
    if ($post->post_type === 'nias_requests' && $post->post_status === 'publish') {
        ?>
        <div style="margin: 10px 0;">
            <button type="button" class="button" onclick="resendToTelegram(<?php echo $post->ID; ?>)">
                ارسال مجدد به تلگرام
            </button>
            <span id="telegram-resend-status"></span>
        </div>
        <script>
        function resendToTelegram(postId) {
            var status = document.getElementById('telegram-resend-status');
            status.textContent = 'در حال ارسال...';
            
            jQuery.post(ajaxurl, {
                action: 'resend_to_telegram',
                post_id: postId,
                nonce: '<?php echo wp_create_nonce('resend_to_telegram'); ?>'
            }, function(response) {
                status.textContent = response.success ? 'با موفقیت ارسال شد!' : 'خطا در ارسال: ' + response.data;
            });
        }
        </script>
        <?php
    }
}
add_action('post_submitbox_misc_actions', 'add_resend_telegram_button');

/**
 * پردازش درخواست AJAX برای ارسال مجدد پست به تلگرام
 */
function handle_resend_to_telegram() {
    check_ajax_referer('resend_to_telegram', 'nonce');
    
    $post_id = intval($_POST['post_id']);
    if (!$post_id) wp_send_json_error('شناسه پست نامعتبر است.');
    
    delete_post_meta($post_id, 'telegram_posted'); // پاک کردن متای قبلی
    send_post_to_telegram($post_id); // ارسال مجدد
    
    wp_send_json_success();
}
add_action('wp_ajax_resend_to_telegram', 'handle_resend_to_telegram');
