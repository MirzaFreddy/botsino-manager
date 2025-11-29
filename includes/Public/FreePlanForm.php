<?php
namespace BotsinoManager\PublicArea;

defined('ABSPATH') || exit;

use BotsinoManager\Config\Constants;

class FreePlanForm {
    
    public function render() {
        $form_id = 'botsino-form-' . uniqid();
        $product_id = get_option('botsino_free_plan_product_id');
        $nonce = wp_create_nonce('botsino_free_plan_submit');
        
        ob_start();
        ?>
        <div class="botsino-popup-form" id="<?php echo esc_attr($form_id); ?>">
            <h3 class="botsino-title">فعال‌سازی ربات واتساپ رایگان اولترابات</h3>
            <form class="botsino-free-plan-form">
                <input type="hidden" name="botsino_nonce" id="botsino_nonce" value="<?php echo esc_attr($nonce); ?>">
                <div class="form-group">
                    <label for="fullname">نام کامل</label>
                    <input type="text" id="fullname" name="fullname" required>
                </div>
                <div class="form-group">
                    <label for="email">ایمیل</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="whatsapp">شماره واتساپ</label>
                    <input type="tel" id="whatsapp" name="whatsapp" pattern="09\d{9}" required>
                </div>
                <input type="hidden" name="product_id" value="<?php echo esc_attr($product_id); ?>">
                <button type="submit" class="botsino-submit-btn">فعال‌سازی رایگان</button>
                <div class="botsino-message"></div>
            </form>
        </div>
        <style>
            .botsino-popup-form { max-width: 500px; margin: 0 auto; padding: 25px; background: #fff; border-radius: 10px; }
            .form-group { margin-bottom: 20px; }
            .form-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; }
            .botsino-submit-btn { width: 100%; padding: 14px; background: #2ecc71; color: white; border: none; border-radius: 6px; }
        </style>
        <script>
     jQuery(document).ready(function($) {
  const $wrap = $('#<?php echo $form_id; ?>');
  const $form = $wrap.find('form');

  $form.on('submit', function(e) {
    e.preventDefault();
    const $thisForm = $(this);
    const $btn = $thisForm.find('.botsino-submit-btn');
    const $msg = $thisForm.find('.botsino-message'); // اسکوپ‌شده به فرم
    const formEl = this;

    // نرمال‌سازی اعداد فارسی به لاتین
    const toEnglishDigits = s => (s || '').replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d));
    const values = {
      fullname: toEnglishDigits($thisForm.find('[name="fullname"]').val()),
      email:    $thisForm.find('[name="email"]').val(),
      whatsapp: toEnglishDigits($thisForm.find('[name="whatsapp"]').val()),
      product_id: $thisForm.find('[name="product_id"]').val(),
      botsino_nonce: $thisForm.find('[name="botsino_nonce"]').val()
    };

    // مقداردهی مجددِ فیلدها بعد از نرمال‌سازی (اختیاری ولی مفید)
    $thisForm.find('[name="whatsapp"]').val(values.whatsapp);

    // چک اعتبار HTML5 بر اساس همین فرم
    if (!formEl.checkValidity()) {
      formEl.reportValidity();
      return;
    }

    // لاگ دقیق از همین فرم
    console.info('Botsino Debug: pre_submit', { origin: 'client', values });

    $btn.prop('disabled', true).text('در حال پردازش...');
    $msg.empty();
    
    // ابتدا نانس جدید دریافت کن تا با کش تداخل نداشته باشد
    $.post('<?php echo admin_url('admin-ajax.php'); ?>', { action: 'botsino_get_freeplan_nonce' })
      .done(function(nonceResp) {
        if (nonceResp && nonceResp.success && nonceResp.data && nonceResp.data.nonce) {
          values.botsino_nonce = nonceResp.data.nonce;
          $thisForm.find('[name="botsino_nonce"]').val(values.botsino_nonce);
        }
      })
      .always(function() {
        // سپس درخواست اصلی را ارسال کن
        $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
          action: 'botsino_process_free_plan',
          botsino_nonce: values.botsino_nonce,
          fullname: values.fullname,
          email: values.email,
          whatsapp: values.whatsapp,
          product_id: values.product_id
        })
        .done(function(response) {
          if (response && response.success) {
            $msg.html('<p style="color:green; padding:10px; background:#d4edda; border-radius:5px;">' + response.data.message + '</p>');
            formEl.reset();
          } else {
            console.warn('Botsino Debug: server_invalid', { origin: 'server', response });
            const msg = response && response.data && response.data.message ? response.data.message : 'خطا در پردازش';
            $msg.html('<p style="color:#721c24; padding:10px; background:#f8d7da; border-radius:5px;">' + msg + '</p>');
          }
        })
        .fail(function(xhr) {
          console.error('Botsino Debug: ajax_failed', { status: xhr.status, responseText: xhr.responseText });
          $msg.html('<p style="color:#721c24; padding:10px; background:#f8d7da; border-radius:5px;">عدم دسترسی به سرور</p>');
        })
        .always(function() {
          $btn.prop('disabled', false).text('فعال‌سازی رایگان');
        });
      });
  });
});

        </script>
        <?php
        return ob_get_clean();
    }
    
    public function handle_ajax() {
        check_ajax_referer('botsino_free_plan_submit', 'botsino_nonce');
        
        if (!isset($_POST['fullname'], $_POST['email'], $_POST['whatsapp'], $_POST['product_id'])) {
            wp_send_json_error(['message' => 'لطفا تمام فیلدهای ضروری را پر کنید']);
        }
        
        $product_id = absint($_POST['product_id']);
        $fullname = sanitize_text_field($_POST['fullname']);
        $email = sanitize_email($_POST['email']);
        $whatsapp = sanitize_text_field($_POST['whatsapp']);
        
        $email_valid = is_email($email);
        $whatsapp_valid = preg_match('/^09\d{9}$/', $whatsapp) === 1;
        if (!$email_valid || !$whatsapp_valid) {
            wp_send_json_error([
                'message' => 'اطلاعات نامعتبر',
                'source' => 'server',
                'code' => 'validation_failed',
                'detail' => [
                    'email_valid' => $email_valid,
                    'whatsapp_valid' => $whatsapp_valid,
                    'whatsapp_length' => strlen($whatsapp),
                ]
            ]);
        }
        
        // بررسی کاربر تکراری قبل از ارسال پیام
        require_once BOTSINO_PLUGIN_DIR . 'includes/API/APIClient.php';
        require_once BOTSINO_PLUGIN_DIR . 'includes/Helpers/Logger.php';
        
        $api = new \BotsinoManager\API\APIClient();
        
        // بررسی شماره موبایل
        $existing_phone_user = $api->get_user_by_phone($whatsapp);
        if ($existing_phone_user) {
            \BotsinoManager\Helpers\Logger::warning(
                'user_creation',
                'تلاش برای ثبت‌نام با شماره تکراری از فرم سایت',
                ['phone' => $whatsapp, 'email' => $email, 'existing_email' => $existing_phone_user['email'] ?? 'N/A']
            );
            
            wp_send_json_error(['message' => '❌ این شماره موبایل قبلاً استفاده شده است', 'source' => 'server', 'code' => 'duplicate_phone']);
            wp_die();
        }
        
        // بررسی ایمیل
        $existing_email_user = $api->get_user($email);
        if ($existing_email_user) {
            \BotsinoManager\Helpers\Logger::warning(
                'user_creation',
                'تلاش برای ثبت‌نام با ایمیل تکراری از فرم سایت',
                ['email' => $email, 'phone' => $whatsapp, 'existing_username' => $existing_email_user['username'] ?? 'N/A']
            );
            
            wp_send_json_error(['message' => '❌ این ایمیل قبلاً استفاده شده است', 'source' => 'server', 'code' => 'duplicate_email']);
            wp_die();
        }
        
        // ارسال پیام تایید فقط برای کاربران جدید (هم شماره و هم ایمیل جدید است)
        require_once BOTSINO_PLUGIN_DIR . 'includes/Notifications/WhatsAppSender.php';
        $wa = new \BotsinoManager\Notifications\WhatsAppSender();
        $wa->send_instant_confirmation($whatsapp, $fullname);
        
        $order = wc_create_order();
        $order->add_product(wc_get_product($product_id), 1);
        $order->set_billing_first_name($fullname);
        $order->set_billing_email($email);
        $order->set_billing_phone($whatsapp);
        $order->calculate_totals();
        $order->update_meta_data('_botsino_processed_by_manager', 'yes');
        $order->update_status('completed');
        
        global $wpdb;
        $table_name = $wpdb->prefix . Constants::QUEUE_TABLE;
        $wpdb->insert($table_name, [
            'order_id' => $order->get_id(),
            'user_data' => serialize(['email' => $email, 'phone' => $whatsapp, 'fullname' => $fullname, 'plan_id' => 1]),
            'created_at' => current_time('mysql'),
            'status' => 'pending',
            'attempts' => 0
        ]);
        
        // پردازش همزمان صف برای انجام فوری ساخت کاربر و ارسال پیام‌ها
        require_once BOTSINO_PLUGIN_DIR . 'includes/Queue/QueueManager.php';
        $queue_manager = new \BotsinoManager\Queue\QueueManager();
        $processed_count = $queue_manager->process();
        
        if ($processed_count > 0) {
            wp_send_json_success(['message' => '✅ درخواست شما ثبت شد و پردازش شد']);
        } else {
            // اگر به هر دلیل پردازشی انجام نشد، همچنان موفق ولی با توضیح
            wp_send_json_success(['message' => '✅ درخواست شما ثبت شد. پردازش به‌زودی انجام می‌شود']);
        }
        wp_die();
    }
    
    public function get_nonce_ajax() {
        wp_send_json_success(['nonce' => wp_create_nonce('botsino_free_plan_submit')]);
    }
}
