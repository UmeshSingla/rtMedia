<?php

/**
 * Description of BPMediaEncoding
 *
 * @author Joshua Abenazer <joshua.abenazer@rtcamp.com>
 */
class RTMediaEncoding {

    protected $api_url = 'http://api.rtcamp.com/';
    protected $sandbox_testing = 0;
    protected $merchant_id = 'paypal@rtcamp.com';

    public function __construct() {
        $this->api_key = get_site_option('rt-media-encoding-api-key');

        if (is_admin()) {

//            add_action('admin_init', array($this, 'encoding_settings'));

            if ($this->api_key)
                add_action('rt_media_before_default_admin_widgets', array($this, 'usage_widget'));
        }

        add_action('admin_init', array($this, 'save_api_key'), 1);

        if ($this->api_key) {
            $usage_info = get_site_option('rt-media-encoding-usage');
            if ($usage_info) {
                if (isset($usage_info[$this->api_key]->status) && $usage_info[$this->api_key]->status) {
                    if (isset($usage_info[$this->api_key]->remaining) && $usage_info[$this->api_key]->remaining > 0) {
                        if ($usage_info[$this->api_key]->remaining < 524288000 && !get_site_option('rt-media-encoding-usage-limit-mail'))
                            $this->nearing_usage_limit($usage_info);
                        elseif ($usage_info[$this->api_key]->remaining > 524288000 && get_site_option('rt-media-encoding-usage-limit-mail'))
                            update_site_option('rt-media-encoding-usage-limit-mail', 0);
                        /**
                         * @todo update class_name
                         */
                        if (!class_exists('RTMediaFFMPEG') && !class_exists('RTMediaKaltura'))
                            add_filter('rt_media_after_add_media', array($this, 'encoding'), 10, 3);
                        $blacklist = array('localhost', '127.0.0.1');
                        if (!in_array($_SERVER['HTTP_HOST'], $blacklist)) {
                            add_filter('rt_media_plupload_files_filter', array($this, 'allowed_types'));
                            add_filter('rt_media_allowed_types', array($this, 'allowed_types'));
                        }
                    }
                }
            }
        }

        add_action('init', array($this, 'handle_callback'), 20);
        add_action('wp_ajax_rt_media_free_encoding_subscribe', array($this, 'free_encoding_subscribe'));
        add_action('wp_ajax_rt_media_unsubscribe_encoding_service', array($this, 'unsubscribe_encoding'));
        add_action('wp_ajax_rt_media_hide_encoding_notice', array($this, 'hide_encoding_notice'), 1);
        add_action('wp_ajax_rt_media_enter_api_key', array($this, 'enter_api_key'), 1);
        add_action('wp_ajax_rt_media_disable_encoding', array($this, 'disable_encoding'), 1);
    }

    function encoding($media_ids, $file_object, $uploaded) {
        foreach ($file_object as $key => $single) {
            if (preg_match('/video|audio/i', $single['type'], $type_array) && !in_array($single['type'], array('audio/mp3', 'video/mp4'))) {

                $query_args = array('url' => urlencode($single['url']),
                    'callbackurl' => urlencode(home_url()),
                    'force' => 0,
                    'size' => filesize($single['file']),
                    'formats' => ($type_array[0] == 'video') ? 'mp4' : 'mp3');
                $encoding_url = $this->api_url . 'job/new/';
                $upload_url = add_query_arg($query_args, $encoding_url . $this->api_key);
                $upload_page = wp_remote_get($upload_url, array('timeout' => 20));

                if (!is_wp_error($upload_page) && (!isset($upload_page['headers']['status']) || (isset($upload_page['headers']['status']) && ($upload_page['headers']['status'] == 200)))) {
                    $upload_info = json_decode($upload_page['body']);
                    if (isset($upload_info->status) && $upload_info->status && isset($upload_info->job_id) && $upload_info->job_id) {
                        $job_id = $upload_info->job_id;
                        update_rtmedia_meta($media_ids[$key], 'rt-media-encoding-job-id', $job_id);
                    } else {
//                        remove_filter('bp_media_plupload_files_filter', array($bp_media_admin->bp_media_encoding, 'allowed_types'));
//                        return parent::insert_media($name, $description, $album_id, $group, $is_multiple, $is_activity, $parent_fallback_files, $author_id, $album_name);
                    }
                }
                $this->update_usage($this->api_key);
//                $this->usage_quota_over();
            }
        }
    }

//    function transcoder($class, $type) {
//        switch ($type) {
//            case 'video':
//            case 'audio':
//                $blacklist = array('localhost', '127.0.0.1');
//                if (in_array($_SERVER['HTTP_HOST'], $blacklist)) {
//                    return $class;
//                }
//
//                if (isset($_FILES['rt_media_file'])) {
//                    $ext = end(explode(".", $_FILES['rt_media_file']["name"]));
//                    if (in_array($_FILES['rt_media_file']['type'], array('audio/mp3', 'video/mp4')) || in_array($ext, array('mp3', 'mp4'))) {
//                        return $class;
//                    }
//                }
//                return 'RTMediaEncodingTranscoder';
//            default:
//                return $class;
//        }
//    }
//    public function menu() {
//        add_submenu_page('bp-media-settings', __('BuddyPress Media Audio/Video Encoding Service', 'rt-media'), __('Audio/Video Encoding', 'rt-media'), 'manage_options', 'bp-media-encoding', array($this, 'encoding_page'));
//        global $submenu;
//        if (isset($submenu['bp-media-settings'])) {
//            $menu = $submenu['bp-media-settings'];
//            $encoding_menu = array_pop($menu);
//            $submenu['bp-media-settings'] = array_merge(array_slice($menu, 0, 2), array($encoding_menu), array_slice($menu, 2));
//        }
//    }
//    /**
//     * Render the BuddyPress Media Encoding page
//     */
//    public function encoding_page() {
//        global $rt_media_admin;
//        $rt_media_admin->render_page('rt-media-encoding');
//    }
//    public function encoding_settings() {
//        add_settings_section('rtm-encoding', __('Audio/Video Encoding Service', 'rt-media'), array($this, 'encoding_service_intro'), 'rt-media-encoding');
//    }
//    public function encoding_tab($tabs) {
//        $encoding_tab = array(
//            'href' => get_admin_url(add_query_arg(array('page' => 'rt-media-encoding'), 'admin.php')),
////                    'name' => __('Audio/Video Encoding', 'rt'),
//            'name' => __('Encoding', 'rt'),
//            'slug' => 'rt-media-encoding'
//        );
//
//        $reordered_tabs = NULL;
//        if (count($tabs) > 2) {
//            foreach ($tabs as $key => $tab) {
//                if ($key == 2)
//                    $reordered_tabs[] = $encoding_tab;
//                $reordered_tabs[] = $tab;
//            }
//            $tabs = $reordered_tabs;
//        } else {
//            $tabs[] = $encoding_tab;
//        }
//        return $tabs;
//    }
//    public function admin_bar_menu($rt_media_admin_nav) {
//// Encoding Service
//        $admin_nav = array(
//            'parent' => 'rt-media-menu',
//            'id' => 'rt-media-encoding',
//            'title' => __('Audio/Video Encoding', 'rt-media'),
//            'href' => get_admin_url(add_query_arg(array('page' => 'rt-media-encoding'), 'admin.php'))
//        );
//        $reordered_admin_nav = NULL;
//        if (count($rt_media_admin_nav) > 2) {
//            foreach ($rt_media_admin_nav as $key => $nav) {
//                if ($key == 3)
//                    $reordered_admin_nav[] = $admin_nav;
//                $reordered_admin_nav[] = $nav;
//            }
//            $rt_media_admin_nav = $reordered_admin_nav;
//        } else {
//            $rt_media_admin_nav[] = $admin_nav;
//        }
//        return $rt_media_admin_nav;
//    }

    public function is_valid_key($key) {
        $validate_url = trailingslashit($this->api_url) . 'api/validate/' . $key;
        $validation_page = wp_remote_get($validate_url, array('timeout' => 20));
        if (!is_wp_error($validation_page)) {
            $validation_info = json_decode($validation_page['body']);
            $status = $validation_info->status;
        } else {
            $status = false;
        }
        return $status;
    }

    public function update_usage($key) {
        $usage_url = trailingslashit($this->api_url) . 'api/usage/' . $key;
        $usage_page = wp_remote_get($usage_url, array('timeout' => 20));
        if (!is_wp_error($usage_page))
            $usage_info = json_decode($usage_page['body']);
        else
            $usage_info = NULL;
        update_site_option('rt-media-encoding-usage', array($key => $usage_info));
        return $usage_info;
    }

    public function nearing_usage_limit($usage_details) {
        $subject = __('BuddyPress Media Encoding: Nearing quota limit.', 'rt-media');
        $message = __('<p>You are nearing the quota limit for your BuddyPress Media encoding service.</p><p>Following are the details:</p><p><strong>Used:</strong> %s</p><p><strong>Remaining</strong>: %s</p><p><strong>Total:</strong> %s</p>', 'rt-media');
        $users = get_users(array('role' => 'administrator'));
        if ($users) {
            foreach ($users as $user)
                $admin_email_ids[] = $user->user_email;
            add_filter('wp_mail_content_type', create_function('', 'return "text/html";'));
            wp_mail($admin_email_ids, $subject, sprintf($message, size_format($usage_details[$this->api_key]->used, 2), size_format($usage_details[$this->api_key]->remaining, 2), size_format($usage_details[$this->api_key]->total, 2)));
        }
        update_site_option('rt-media-encoding-usage-limit-mail', 1);
    }

    public function usage_quota_over() {
        $usage_details = get_site_option('rt-media-encoding-usage');
        if (!$usage_details[$this->api_key]->remaining) {
            $subject = __('BuddyPress Media Encoding: Usage quota over.', 'rt-media');
            $message = __('<p>Your usage quota is over. Upgrade your plan</p><p>Following are the details:</p><p><strong>Used:</strong> %s</p><p><strong>Remaining</strong>: %s</p><p><strong>Total:</strong> %s</p>', 'rt-media');
            $users = get_users(array('role' => 'administrator'));
            if ($users) {
                foreach ($users as $user)
                    $admin_email_ids[] = $user->user_email;
                add_filter('wp_mail_content_type', create_function('', 'return "text/html";'));
                wp_mail($admin_email_ids, $subject, sprintf($message, size_format($usage_details[$this->api_key]->used, 2), 0, size_format($usage_details[$this->api_key]->total, 2)));
            }
            update_site_option('rt-media-encoding-usage-limit-mail', 1);
        }
    }

    public function save_api_key() {
        if (isset($_GET['api_key_updated']) && $_GET['api_key_updated']) {
            if (is_multisite()) {
                add_action('network_admin_notices', array($this, 'successfully_subscribed_notice'));
            }
            add_action('admin_notices', array($this, 'successfully_subscribed_notice'));
        }
        if (isset($_GET['apikey']) && is_admin() && isset($_GET['page']) && ($_GET['page'] == 'rt-media-addons') && $this->is_valid_key($_GET['apikey'])) {
            if ($this->api_key && !(isset($_GET['update']) && $_GET['update'])) {
                $unsubscribe_url = trailingslashit($this->api_url) . 'api/cancel/' . $this->api_key;
                wp_remote_post($unsubscribe_url, array('timeout' => 120, 'body' => array('note' => 'Direct URL Input (API Key: ' . $_GET['apikey'] . ')')));
            }
            update_site_option('rt-media-encoding-api-key', $_GET['apikey']);
            $usage_info = $this->update_usage($_GET['apikey']);
            $return_page = add_query_arg(array('page' => 'rt-media-addons', 'api_key_updated' => $usage_info->plan->name), (is_multisite() ? network_admin_url('admin.php') : admin_url('admin.php')));
            wp_safe_redirect($return_page);
        }
    }

    public function allowed_types($types) {
        if (isset($types[0]) && isset($types[0]['extensions'])) {
            $types[0]['extensions'] .= 'mov,m4v,m2v,avi,mpg,flv,wmv,mkv,webm,ogv,mxf,asf,vob,mts,qt,mpeg'; //Allow all types of file to be uploded
            $types[0]['extensions'] .= 'wma,ogg,wav,m4a'; //Allow all types of file to be uploded
        } else {
            if (isset($types['video'])) {
                $video_types = explode(',', 'mov,m4v,m2v,avi,mpg,flv,wmv,mkv,webm,ogv,mxf,asf,vob,mts,qt,mpeg');
                $types['video']['extn'] = array_merge($types['video']['extn'], $video_types);
            }
            if (isset($types['audio'])) {
                $audio_types = explode(',', 'wma,ogg,wav,m4a');
                $types['audio']['extn'] = array_merge($types['audio']['extn'], $audio_types);
            }
        }
        return $types;
    }

    public function successfully_subscribed_notice() {
        ?>
        <div class="updated">
            <p><?php printf(__('You have successfully subscribed for the <strong>%s</strong> plan', 'rt-media'), $_GET['api_key_updated']); ?></p>
        </div><?php
    }

    public function encoding_subscription_form($name = 'No Name', $price = '0', $force = false) {
        if ($this->api_key)
            $this->update_usage($this->api_key);
        $action = $this->sandbox_testing ? 'https://sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';
        $return_page = add_query_arg(array('page' => 'rt-media-addons'), (is_multisite() ? network_admin_url('admin.php') : admin_url('admin.php')));

        $usage_details = get_site_option('rt-media-encoding-usage');
        if (isset($usage_details[$this->api_key]->plan->name) && (strtolower($usage_details[$this->api_key]->plan->name) == strtolower($name)) && $usage_details[$this->api_key]->sub_status && !$force) {
            $form = '<button data-plan="' . $name . '" data-price="' . $price . '" type="submit" class="button bpm-unsubscribe">' . __('Unsubscribe', 'rt-media') . '</button>';
            $form .= '<div id="bpm-unsubscribe-dialog" title="Unsubscribe">
  <p>Just to improve our service we would like to know the reason for you to leave us.</p>
  <p><textarea rows="3" cols="36" id="bpm-unsubscribe-note"></textarea></p>
</div>';
        } else {
            $form = '<form method="post" action="' . $action . '" class="paypal-button" target="_top">
                        <input type="hidden" name="button" value="subscribe">
                        <input type="hidden" name="item_name" value="' . ucfirst($name) . '">

                        <input type="hidden" name="currency_code" value="USD">


                        <input type="hidden" name="a3" value="' . $price . '">
                        <input type="hidden" name="p3" value="1">
                        <input type="hidden" name="t3" value="M">

                        <input type="hidden" name="cmd" value="_xclick-subscriptions">

                        <!-- Merchant ID -->
                        <input type="hidden" name="business" value="' . $this->merchant_id . '">


                        <input type="hidden" name="custom" value="' . $return_page . '">

                        <!-- Flag to no shipping -->
                        <input type="hidden" name="no_shipping" value="1">

                        <input type="hidden" name="notify_url" value="' . trailingslashit($this->api_url) . 'subscribe/paypal">

                        <!-- Flag to post payment return url -->
                        <input type="hidden" name="return" value="' . trailingslashit($this->api_url) . 'payment/process">


                        <!-- Flag to post payment data to given return url -->
                        <input type="hidden" name="rm" value="2">

                        <input type="hidden" name="src" value="1">
                        <input type="hidden" name="sra" value="1">

                        <input type="image" src="http://www.paypal.com/en_US/i/btn/btn_subscribe_SM.gif" border="0" name="submit" alt="Make payments with PayPal - it\'s fast, free and secure!">
                    </form>';
        }
        return $form;
    }

    public function usage_widget() {
        $usage_details = get_site_option('rt-media-encoding-usage');
        $content = '';
        if ($usage_details && isset($usage_details[$this->api_key]->status) && $usage_details[$this->api_key]->status) {
            if (isset($usage_details[$this->api_key]->plan->name))
                $content .= '<p><strong>' . __('Current Plan', 'rt-media') . ':</strong> ' . $usage_details[$this->api_key]->plan->name . ($usage_details[$this->api_key]->sub_status ? '' : ' (' . __('Unsubscribed', 'rt-media') . ')') . '</p>';
            if (isset($usage_details[$this->api_key]->used))
                $content .= '<p><span class="encoding-used"></span><strong>' . __('Used', 'rt-media') . ':</strong> ' . (($used_size = size_format($usage_details[$this->api_key]->used, 2)) ? $used_size : '0MB') . '</p>';
            if (isset($usage_details[$this->api_key]->remaining))
                $content .= '<p><span class="encoding-remaining"></span><strong>' . __('Remaining', 'rt-media') . ':</strong> ' . (($remaining_size = size_format($usage_details[$this->api_key]->remaining, 2)) ? $remaining_size : '0MB') . '</p>';
            if (isset($usage_details[$this->api_key]->total))
                $content .= '<p><strong>' . __('Total', 'rt-media') . ':</strong> ' . size_format($usage_details[$this->api_key]->total, 2) . '</p>';
            $usage = new rtProgress();
            $content .= $usage->progress_ui($usage->progress($usage_details[$this->api_key]->used, $usage_details[$this->api_key]->total), false);
            if ($usage_details[$this->api_key]->remaining <= 0)
                $content .= '<div class="error below-h2"><p>' . __('Your usage limit has been reached. Upgrade your plan.', 'rt-media') . '</p></div>';
        } else {
            $content .= '<div class="error below-h2"><p>' . __('Your API key is not valid or is expired.', 'rt-media') . '</p></div>';
        }
        new RTMediaAdminWidget('rt-media-encoding-usage', __('Encoding Usage', 'rt-media'), $content);
    }

    public function encoding_service_intro() {
        ?>
        <p><?php _e('BuddyPress Media team has started offering an audio/video encoding service.', 'rt-media'); ?></p>
        <p>
            <label for="new-api-key"><?php _e('Enter API KEY', 'rt-media'); ?></label>
            <input id="new-api-key" type="text" name="new-api-key" value="<?php echo $this->api_key; ?>" size="60" />
            <input type="submit" id="api-key-submit" name="api-key-submit" value="<?php echo __('Submit', 'rt-media'); ?>" class="button-primary" />
            <?php if ($this->api_key) { ?><br /><br /><input type="submit" id="disable-encoding" name="disable-encoding" value="Disable Encoding" class="button-secondary" /><?php } ?>
        </p>
        <table  class="bp-media-encoding-table widefat fixed" cellspacing="0">
            <tbody>
                <!-- Results table headers -->
            <thead>
                <tr>
                    <th><?php _e('Feature\Plan', 'rt-media'); ?></th>
                    <th><?php _e('Free', 'rt-media'); ?></th>
                    <th><?php _e('Silver', 'rt-media'); ?></th>
                    <th><?php _e('Gold', 'rt-media'); ?></th>
                    <th><?php _e('Platinum', 'rt-media'); ?></th>
                </tr>
            </thead>
            <tr>
                <th><?php _e('File Size Limit', 'rt-media'); ?></th>
                <td>200MB (<del>20MB</del>)</td>
                <td colspan="3" class="column-posts">16GB (<del>2GB</del>)</td>
            </tr>
            <tr>
                <th><?php _e('Bandwidth (monthly)', 'rt-media'); ?></th>
                <td>10GB (<del>1GB</del>)</td>
                <td>100GB</td>
                <td>1TB</td>
                <td>10TB</td>
            </tr>
            <tr>
                <th><?php _e('Overage Bandwidth', 'rt-media'); ?></th>
                <td><?php _e('Not Available', 'rt-media'); ?></td>
                <td>$0.10 per GB</td>
                <td>$0.08 per GB</td>
                <td>$0.05 per GB</td>
            </tr>
            <tr>
                <th><?php _e('Amazon S3 Support', 'rt-media'); ?></th>
                <td><?php _e('Not Available', 'rt-media'); ?></td>
                <td colspan="3" class="column-posts"><?php _e('Coming Soon', 'rt-media'); ?></td>
            </tr>
            <tr>
                <th><?php _e('HD Profile', 'rt-media'); ?></th>
                <td><?php _e('Not Available', 'rt-media'); ?></td>
                <td colspan="3" class="column-posts"><?php _e('Coming Soon', 'rt-media'); ?></td>
            </tr>
            <tr>
                <th><?php _e('Webcam Recording', 'rt-media'); ?></th>
                <td colspan="4" class="column-posts"><?php _e('Coming Soon', 'rt-media'); ?></td>
            </tr>
            <tr>
                <th><?php _e('Pricing', 'rt-media'); ?></th>
                <td><?php _e('Free', 'rt-media'); ?></td>
                <td><?php _e('$9/month', 'rt-media'); ?></td>
                <td><?php _e('$99/month', 'rt-media'); ?></td>
                <td><?php _e('$999/month', 'rt-media'); ?></td>
            </tr>
            <tr>
                <th></th>
                <td><?php
        $usage_details = get_site_option('rt-media-encoding-usage');
        if (isset($usage_details[$this->api_key]->plan->name) && (strtolower($usage_details[$this->api_key]->plan->name) == 'free')) {
            echo '<button disabled="disabled" type="submit" class="encoding-try-now button button-primary">' . __('Current Plan', 'rt-media') . '</button>';
        } else {
                ?>
                        <form id="encoding-try-now-form" method="get" action="">
                            <button type="submit" class="encoding-try-now button button-primary"><?php _e('Try Now', 'rt-media'); ?></button>
                        </form><?php }
            ?>
                </td>
                <td><?php echo $this->encoding_subscription_form('silver', 9.0) ?></td>
                <td><?php echo $this->encoding_subscription_form('gold', 99.0) ?></td>
                <td><?php echo $this->encoding_subscription_form('platinum', 999.0) ?></td>
            </tr>
        </tbody>
        </table><br /><?php
        }

        /**
         * Function to handle the callback request by the FFMPEG encoding server
         *
         * @since 1.0
         */
        public function handle_callback() {
            if (isset($_GET['job_id']) && isset($_GET['download_url'])) {
                $flag = false;
                global $wpdb;
                $model = new RTDBModel('rtm_media_meta');
                $meta_details = $model->get(array('meta_value' => $_GET['job_id'], 'meta_key' => 'rt-media-encoding-job-id'));
                if (isset($meta_details[0])) {
                    $id = maybe_unserialize($meta_details[0]->media_id);
                    $model = new RTMediaModel();
                    $media = $model->get_media(array('id' => $id), 0, 1);
                    $this->media_author = $media[0]->media_author;
                    $attachment_id = $media[0]->media_id;

                    $download_url = urldecode($_GET['download_url']);
                    $new_wp_attached_file_pathinfo = pathinfo($download_url);
                    $post_mime_type = $new_wp_attached_file_pathinfo['extension'] == 'mp4' ? 'video/mp4' : 'audio/mp3';
                    try {
                        $file_bits = file_get_contents($download_url);
                    } catch (Exception $e) {
                        $flag = $e->getMessage();
                    }
                    if ($file_bits) {
                        unlink(get_attached_file($attachment_id));
                        add_filter('upload_dir', array($this, 'upload_dir'));
                        $upload_info = wp_upload_bits($new_wp_attached_file_pathinfo['basename'], null, $file_bits);
                        $wpdb->update($wpdb->posts, array('guid' => $upload_info['url'], 'post_mime_type' => $post_mime_type), array('ID' => $attachment_id));
                        $old_wp_attached_file = get_post_meta($attachment_id, '_wp_attached_file', true);
                        $old_wp_attached_file_pathinfo = pathinfo($old_wp_attached_file);
                        update_post_meta($attachment_id, '_wp_attached_file', str_replace($old_wp_attached_file_pathinfo['basename'], $new_wp_attached_file_pathinfo['basename'], $old_wp_attached_file));
//                        $media_entry = new BPMediaHostWordpress($attachment_id);
//                        $activity_content = str_replace($old_wp_attached_file_pathinfo['basename'], $new_wp_attached_file_pathinfo['basename'], $media_entry->get_media_activity_content());
//                        $wpdb->update($wpdb->prefix . 'bp_activity', array('content' => $activity_content), array('id' => $media[0]->activity_id));
                        // Check if uplaod is through activity upload
//                        $activity_id = get_post_meta($attachment_id, 'bp-media-activity-upload-id', true);
//                        if ($activity_id) {
//                            $content = $wpdb->get_var("SELECT content FROM {$wpdb->prefix}bp_activity WHERE id = $activity_id");
//                            $activity_content = str_replace($old_wp_attached_file_pathinfo['basename'], $new_wp_attached_file_pathinfo['basename'], $content);
//                            $wpdb->update($wpdb->prefix . 'bp_activity', array('content' => $activity_content), array('id' => $activity_id));
//                        }
                    } else {
                        $flag = __('Could not read file.', 'rt-media');
                        error_log($flag);
                    }
                } else {
                    $flag = __('Something went wrong. The required attachment id does not exists. It must have been deleted.', 'rt-media');
                    error_log($flag);
                }


                $this->update_usage($this->api_key);

                if (isset($_SERVER['REMOTE_ADDR']) && ($_SERVER['REMOTE_ADDR'] == '4.30.110.155')) {
                    $mail = true;
                } else {
                    $mail = false;
                }

                if ($flag && $mail) {
                    $download_link = add_query_arg(array('job_id' => $_GET['job_id'], 'download_url' => $_GET['download_url']), home_url());
                    $subject = __('BuddyPress Media Encoding: Download Failed', 'rt-media');
                    $message = sprintf(__('<p><a href="%s">Media</a> was successfully encoded but there was an error while downloading:</p>
                        <p><code>%s</code></p>
                        <p>You can <a href="%s">retry the download</a>.</p>', 'rt-media'), get_edit_post_link($attachment_id), $flag, $download_link);
                    $users = get_users(array('role' => 'administrator'));
                    if ($users) {
                        foreach ($users as $user)
                            $admin_email_ids[] = $user->user_email;
                        add_filter('wp_mail_content_type', create_function('', 'return "text/html";'));
                        wp_mail($admin_email_ids, $subject, $message);
                    }
                    _e($flag);
                } elseif ($flag) {
                    _e($flag);
                } else {
                    _e("Done", 'rt-media');
                }
                die();
            }
        }

        public function free_encoding_subscribe() {
            $email = get_site_option('admin_email');
            $usage_details = get_site_option('rt-media-encoding-usage');
            if (isset($usage_details[$this->api_key]->plan->name) && (strtolower($usage_details[$this->api_key]->plan->name) == 'free')) {
                echo json_encode(array('error' => 'Your free subscription is already activated.'));
            } else {
                $free_subscription_url = add_query_arg(array('email' => urlencode($email)), trailingslashit($this->api_url) . 'api/free/');
                if ($this->api_key) {
                    $free_subscription_url = add_query_arg(array('email' => urlencode($email), 'apikey' => $this->api_key), $free_subscription_url);
                }
                $free_subscribe_page = wp_remote_get($free_subscription_url, array('timeout' => 120));
                if (!is_wp_error($free_subscribe_page) && (!isset($free_subscribe_page['headers']['status']) || (isset($free_subscribe_page['headers']['status']) && ($free_subscribe_page['headers']['status'] == 200)))) {
                    $subscription_info = json_decode($free_subscribe_page['body']);
                    if (isset($subscription_info->status) && $subscription_info->status) {
                        echo json_encode(array('apikey' => $subscription_info->apikey));
                    } else {
                        echo json_encode(array('error' => $subscription_info->message));
                    }
                } else {
                    echo json_encode(array('error' => 'Something went wrong please try again.'));
                }
            }
            die();
        }

        public function hide_encoding_notice() {
            update_site_option('rt-media-encoding-service-notice', true);
            update_site_option('rt-media-encoding-expansion-notice', true);
            echo true;
            die();
        }

        public function unsubscribe_encoding() {
            $unsubscribe_url = trailingslashit($this->api_url) . 'api/cancel/' . $this->api_key;
            $unsubscribe_page = wp_remote_post($unsubscribe_url, array('timeout' => 120, 'body' => array('note' => $_GET['note'])));
            if (!is_wp_error($unsubscribe_page) && (!isset($unsubscribe_page['headers']['status']) || (isset($unsubscribe_page['headers']['status']) && ($unsubscribe_page['headers']['status'] == 200)))) {
                $subscription_info = json_decode($unsubscribe_page['body']);
                if (isset($subscription_info->status) && $subscription_info->status) {
                    echo json_encode(array('updated' => __('Your subscription was cancelled successfully', 'rt-media'), 'form' => $this->encoding_subscription_form($_GET['plan'], $_GET['price'])));
                }
            } else {
                echo json_encode(array('error' => __('Something went wrong please try again.', 'rt-media')));
            }
            die();
        }

        public function enter_api_key() {
            if (isset($_GET['apikey'])) {
                echo json_encode(array('apikey' => $_GET['apikey']));
            } else {
                echo json_encode(array('error' => __('Please enter the api key.', 'rt-media')));
            }
            die();
        }

        public function disable_encoding() {
            update_site_option('rt-media-encoding-api-key', '');
            _e('Encoding disabled successfully.', 'rt-media');
            die();
        }

        function upload_dir($upload_dir) {
            global $rt_media_interaction;
            if (isset($this->uploaded["context"]) && isset($this->uploaded["context_id"])) {
                if ($this->uploaded["context"] != 'group') {
                    $rtmedia_upload_prefix = 'users/';
                    $id = get_current_user_id();
                    
                } else {
                    $rtmedia_upload_prefix = 'groups/';
                    $id = $this->uploaded["context_id"];
                }
            } else {
                if ($rt_media_interaction->context->type != 'group') {
                    $rtmedia_upload_prefix = 'users/';
                    $id = get_current_user_id();
                } else {
                    $rtmedia_upload_prefix = 'groups/';
                    $id = $rt_media_interaction->context->id;
                }
            }
            
            if (!$id) {
                $id = $this->media_author;
            }


            $upload_dir['path'] = trailingslashit(
                            str_replace($upload_dir['subdir'], '', $upload_dir['path']))
                    . 'rtMedia/' . $rtmedia_upload_prefix . $id .
                    $upload_dir['subdir'];
            $upload_dir['url'] = trailingslashit(
                            str_replace($upload_dir['subdir'], '', $upload_dir['url']))
                    . 'rtMedia/' . $rtmedia_upload_prefix . $id
                    . $upload_dir['subdir'];

            return $upload_dir;
        }

    }
    ?>
