<?php
/*
Plugin Name: Booking Special Calendar
Plugin URI: https://github.com/elirant3/booking-special-calendar
Description: Manage calendar meeting book. The plugin generate calendar by using wordpress do_shortcode function.
Version: 1.0
Author: Eliran Biton
Author URI: https://github.com/elirant3
License: A "Slug" license name e.g. GPL2
Text Domain: booking-special-calendar
*/
defined('ABSPATH') || exit;

class BSLC_Booking
{
    private static $bslc_db_version = '1.1.0',
        $bslc_asset_version = '1.4.4';
    public static $success_message;

    public static function init()
    {
        register_setting('bslc_settings', 'bslc_booking_option_');
        register_activation_hook(__FILE__, __CLASS__ . '::install');
        register_activation_hook(__FILE__, __CLASS__ . '::insertDemo');
        add_action('plugins_loaded', [__CLASS__, 'updateDBCheck']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'assets']);

        add_action('wp_ajax_bslc_getCalendarHours', [__CLASS__, 'getCalendarHours']);
        add_action('wp_ajax_nopriv_bslc_getCalendarHours', [__CLASS__, 'getCalendarHours']);

        add_action('wp_ajax_bslc_addNewMeetingRequest', [__CLASS__, 'addNewMeetingRequest']);
        add_action('wp_ajax_nopriv_bslc_addNewMeetingRequest', [__CLASS__, 'addNewMeetingRequest']);

        add_shortcode('booking-special-calendar', [__CLASS__, 'getCalendar']);
        add_action('admin_menu', [__CLASS__, 'settingsPage']);
        add_action('admin_notices', [__CLASS__, 'notices']);
        add_action('init', [__CLASS__, 'meetingsRequests']);
        add_action('add_meta_boxes', [__CLASS__, 'addMeetingInfoToPost']);

        add_action('pre_post_update', [__CLASS__, 'checkStatus'], 10, 2);
        add_action('delete_post_special-calendar', [__CLASS__, 'checkStatus']);
        add_action('before_delete_post', [__CLASS__, 'deleteMeeting']);
    }

    public static function deleteMeeting($post_id)
    {
        delete_post_meta($post_id, 'bslc_hourid');

        return true;
    }

    public static function checkStatus($post_id, $post)
    {
        /*validate user permission*/
        if (isset($_POST['post_type']) && 'page' == $_POST['post_type']) {
            if (!current_user_can('edit_page', $post_id)) {
                return;
            }
        } else {
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
        }

        /*verify its not autosave*/
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // If this is a revision, get real post ID
        if ($parent_id = wp_is_post_revision($post_id)) {
            return;
        }

        if (is_numeric(get_post_meta($post_id, 'bslc_hourid', true))) {
            $post = (object)$post;

            if (isset($post->post_status) && !empty($post->post_status)) {
                $post_status = sanitize_text_field($post->post_status);
                if ($post_status) {
                    switch ($post_status) {
                        case 'publish':
                            $status = 'approved';
                            break;
                        default:
                            $status = $post_status;
                    }

                    self::updateHour(get_post_meta($post_id, 'bslc_hourid', true), $status, $post_id);
                }
            }
        }
    }

    public static function addMeetingInfoToPost()
    {
        add_meta_box(
            'bslc_meeting_info_meta_box',
            _x('Meeting info', 'booking-special-calendar'),
            [__CLASS__, 'bslc_addSeoMetaFields'],
            'special-calendar',
            'normal',
            'high'
        );
    }

    public static function bslc_addSeoMetaFields()
    {
        global $post_id;
        ob_start();
        $from = get_post_meta($post_id, 'bslc_from', true);
        $to = get_post_meta($post_id, 'bslc_to', true);
        $fName = get_post_meta($post_id, 'bslc_first_name', true);
        $lName = get_post_meta($post_id, 'bslc_last_name', true);
        $email = get_post_meta($post_id, 'bslc_email', true);
        $phone = get_post_meta($post_id, 'bslc_phone', true);
        if (!$from) {
            return;
        }
        ?>
        <ul>
            <li>
                <p><?= _x('Date', 'booking-special-calendar'); ?></p>
                <p><?= date('Y-m-d', strtotime($from)); ?></p>
            </li>
            <li>
                <span><?= _x('From: ', 'booking-special-calendar'); ?></span>
                <span><?= date('H:i:s', strtotime($from)); ?></span>
                <span> - </span>
                <span><?= _x('To: ', 'booking-special-calendar'); ?></span>
                <span><?= date('H:i:s', strtotime($to)); ?></span>
            </li>
            <li>
                <span><?= _x('First Name: ', 'booking-special-calendar'); ?></span>
                <span><?= sanitize_text_field($fName); ?></span>
            </li>
            <li>
                <span><?= _x('Last Name: ', 'booking-special-calendar'); ?></span>
                <span><?= sanitize_text_field($lName); ?></span>
            </li>
            <li>
                <span><?= _x('Email: ', 'booking-special-calendar'); ?></span>
                <span><?= sanitize_text_field($email); ?></span>
            </li>
            <li>
                <span><?= _x('Phone: ', 'booking-special-calendar'); ?></span>
                <span><?= sanitize_text_field($phone); ?></span>
            </li>
        </ul>
        <?php
        echo ob_get_clean();
    }

    public static function meetingsRequests()
    {
        if (!post_type_exists("special-calendar")) {
            $labels = [
                "name" => __("Meeting Requests", "booking-special-calendar"),
                "singular_name" => __("Meeting", "booking-special-calendar"),
                "menu_name" => __("Meeting Requests", "booking-special-calendar"),
                "all_items" => __("All Requests", "booking-special-calendar"),
                "add_new" => __("New Request", "booking-special-calendar"),
                "add_new_item" => __("Request Name", "booking-special-calendar"),
                "edit_item" => __("Edit Request", "booking-special-calendar"),
                "new_item" => __("View Request", "booking-special-calendar"),
                "view_item" => __("View Request", "booking-special-calendar"),
                "view_items" => __("View Requests", "booking-special-calendar"),
            ];

            $args = [
                "label" => __("Meeting Requests", "ebtech"),
                "labels" => $labels,
                "description" => "",
                "public" => false,
                "publicly_queryable" => false,
                "show_ui" => true,
                "show_in_rest" => false,
                "rest_base" => "",
                "has_archive" => false,
                "show_in_menu" => true,
                "show_in_nav_menus" => true,
                "exclude_from_search" => true,
                "capability_type" => "post",
                "map_meta_cap" => true,
                "hierarchical" => false,
                "rewrite" => ["slug" => "special-calendar-meetings", "with_front" => true],
                "query_var" => true,
                "supports" => [
                    "title",
                ],
            ];

            register_post_type("special-calendar", $args);
        }
    }

    private static function updateHour(int $hourid, string $string, $post_id)
    {
        global $wpdb;

        $format = ['%s'];
        $where = ['id' => $hourid];
        $where_format = ['%d'];

        $sql = "SELECT * FROM " . $wpdb->prefix . "special_booking WHERE id = %d";
        if ($query = $wpdb->get_row($wpdb->prepare($sql, $hourid))) {
            if ($query->post_id == $post_id) {
                if ($string === 'trash' || $string === 'draft') {
                    $mysqlData = ['meta_data' => ''];
                    $wpdb->update($wpdb->prefix . 'special_booking', $mysqlData, $where, $format, $where_format);
                } else {
                    $mysqlData = ['meta_data' => $string];
                    $wpdb->update($wpdb->prefix . 'special_booking', $mysqlData, $where, $format, $where_format);
                    $_SESSION['success_message'] = $string;
                }

                return true;
            } else {
                $errorMsg = _x('Hour register to other meeting.', 'booking-special-calendar');
            }
        } else {
            $errorMsg = _x('Unknown hour.', 'booking-special-calendar');
        }

        if ($errorMsg) {
            $_SESSION['error_message'] = $errorMsg;
        }

        return false;
    }

    private static function validateHourId($hourid)
    {
        $args = [
            'post_type' => 'special-calendar',
            'meta_query' => [
                [
                    'key' => 'bslc_hourid',
                    'value' => [$hourid],
                ]
            ],
        ];

        $validateHour = new WP_Query($args);

        return $validateHour->post_count;
    }

    private static function createPostFromHour(string $completeDate)
    {
        $insert = false;
        $args = [
            'post_title' => 'Hour - ' . $completeDate,
            'post_type' => 'bslc_hourpost',
            'post_content' => $completeDate,
            'post_status' => 'private',
            'post_date' => date('Y-m-d H:i:s', strtotime($completeDate)),
        ];

        if ($new_post = wp_insert_post($args)) {
            update_post_meta($new_post, 'bslc_from_to_hour', $completeDate);

            return $new_post;
        }

        return $insert;
    }

    public function addNewMeetingRequest()
    {
        $json = [];
        global $wpdb;
        $nonce = filter_input(INPUT_POST, 'submitMeetingNonce', FILTER_SANITIZE_STRING);

        if (!isset($_POST['submitMeetingNonce']) || !wp_verify_nonce($nonce, 'submitMeetingNonce')) {
            $json['error'] = _x('Session has expired.', 'booking-special-calendar');
        }

        if (!isset($json['error'])) {
            if (isset($_POST['hour_id']) && !empty($_POST['hour_id']) && is_numeric($_POST['hour_id']) && $_POST['hour_id'] > 0) {
                $data['hour_id'] = sanitize_text_field($_POST['hour_id']);
                $sql = "SELECT * FROM " . $wpdb->prefix . "special_booking WHERE id = %d";
                if ($query = $wpdb->get_row($wpdb->prepare($sql, $data['hour_id']))) {
                    if (!$query->meta_data) {
                        if (isset($_POST['fName']) && !empty($_POST['fName'])) {
                            $data['fName'] = trim(sanitize_text_field($_POST['fName']));
                        } else {
                            $data['fName'] = '';
                        }

                        if (isset($_POST['lName']) && !empty($_POST['lName'])) {
                            $data['lName'] = trim(sanitize_text_field($_POST['lName']));
                        } else {
                            $data['lName'] = '';
                        }

                        if (isset($_POST['email']) && !empty($_POST['email'])) {
                            if ($email = is_email(trim(sanitize_text_field($_POST['email'])))) {
                                $data['email'] = $email;
                            }
                        } else {
                            $data['email'] = '';
                        }

                        if (isset($_POST['phone']) && !empty($_POST['phone'])) {
                            $data['phone'] = trim(sanitize_text_field($_POST['phone']));
                        } else {
                            $data['phone'] = '';
                        }

                        if (!in_array(null, $data)) {
                            $args = [
                                'post_title' => 'New Request',
                                'post_type' => 'special-calendar',
                                'post_content' => implode(', ', $data),
                                'post_status' => 'pending',
                            ];

                            $new_post = wp_insert_post($args);
                            if ($new_post) {
                                $mysqlData = ['meta_data' => 'pending', 'post_id' => $new_post];
                                $format = ['%s', '%d'];
                                $where = ['id' => $query->id];
                                $where_format = ['%d'];
                                $update = $wpdb->update($wpdb->prefix . 'special_booking', $mysqlData, $where, $format, $where_format);
                                if ($update) {
                                    update_post_meta($new_post, 'bslc_from', $query->h_from);
                                    update_post_meta($new_post, 'bslc_to', $query->h_to);
                                    update_post_meta($new_post, 'bslc_phone', $data['phone']);
                                    update_post_meta($new_post, 'bslc_email', $data['email']);
                                    update_post_meta($new_post, 'bslc_hourid', $query->id);
                                    update_post_meta($new_post, 'bslc_first_name', $data['fName']);
                                    update_post_meta($new_post, 'bslc_last_name', $data['lName']);

                                    $json['success'] = _x('Request accepted. thank you.', 'booking-special-calendar');
                                } else {
                                    $json['error'] = _x('Network Error.', 'booking-special-calendar');
                                }
                            } else {
                                $json['error'] = _x('Network Error.', 'booking-special-calendar');
                            }
                        } else {
                            $json['error'] = _x('All fields are required.', 'booking-special-calendar');
                        }
                    } else {
                        $json['error'] = _x('Unavailable hour.', 'booking-special-calendar');
                    }
                } else {
                    $json['error'] = _x('Unknown meeting hour.', 'booking-special-calendar');
                }
            } else {
                $json['error'] = _x('Unknown meeting hour.', 'booking-special-calendar');
            }
        }

        header('Content-Type: application/json');
        echo json_encode($json);
        die;
    }

    public static function notices()
    {
        if (isset($_GET['success']) && isset($_GET['post_type']) && strtolower(urldecode($_GET['post_type'])) == 'special-calendar') {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?= _x('Hours Created!', 'booking-special-calendar'); ?></p>
            </div>
            <?php
        } else if (isset($_SESSION['success_message']) && !empty($_SESSION['success_message'])) {
            $accept_meeting = sanitize_text_field($_SESSION['success_message']);
            unset($_SESSION['success_message']);
            switch ($accept_meeting) {
                case 'approved':
                    $accept_meeting = _x('Meeting approved.', 'booking-special-calendar');
                    break;
                case 'pending':
                    $accept_meeting = _x('Meeting pending.', 'booking-special-calendar');
                    break;
            }
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?= _x($accept_meeting, 'booking-special-calendar'); ?></p>
            </div>
            <?php
        } else if (isset($_SESSION['error_message']) && !empty($_SESSION['error_message'])) {
            $error_message = sanitize_text_field(urldecode($_SESSION['error_message']));
            unset($_SESSION['error_message']);
            ?>
            <div class="notice notice-danger notice-warning notice-error is-dismissible">
                <p><?= _x($error_message, 'booking-special-calendar'); ?></p>
            </div>
            <?php
        }
    }

    public static function getSuccessMsg()
    {
        return self::$success_message;
    }

    public static function settingsPage()
    {
        add_submenu_page(
            'edit.php?post_type=special-calendar',
            'Special Booking Settings',
            'Settings',
            'manage_options',
            'special-calendar-settings',
            [__CLASS__, 'settingsPageHtml']
        );

        add_submenu_page(
            'edit.php?post_type=special-calendar',
            'Manage hours',
            'Manage Hours',
            'manage_options',
            'special-calendar-hours',
            [__CLASS__, 'hoursPageHtml']
        );
    }

    public static function hoursPageHtml()
    {
        if (is_file(plugin_dir_path(__FILE__) . 'includes/manage_hours.php')) {
            include_once plugin_dir_path(__FILE__) . 'includes/manage_hours.php';
        }
    }

    public static function settingsPageHtml()
    {
        $error = false;
        global $wpdb;

        if (isset($_POST['s_booking_submit'])) {
            if (
                !isset($_POST['s_booking_add'])
                || !wp_verify_nonce($_POST['s_booking_add'], 's_booking_add')
            ) {
                print 'Sorry, your nonce did not verify.';
                exit;
            } else {
                // process form data
                $data['bslc_day'] = filter_input(INPUT_POST, 'bslc_day', FILTER_SANITIZE_STRING);
                $data['bslc_from'] = filter_input(INPUT_POST, 'bslc_from', FILTER_VALIDATE_INT);
                $data['bslc_to'] = filter_input(INPUT_POST, 'bslc_to', FILTER_VALIDATE_INT);
                $data['bslc_jumps'] = filter_input(INPUT_POST, 'bslc_jumps', FILTER_VALIDATE_INT);
                if (!in_array(null, $data)) {
                    if ($data['bslc_from'] < $data['bslc_to'] && $data['bslc_to'] <= 24) {
                        try {
                            $day = new DateTime($data['bslc_day']);
                            if ($day) {
                                $jump = ($data['bslc_jumps'] == 30 || $data['bslc_jumps'] == 60) ? $data['bslc_jumps'] : false;
                                $h_from = $data['bslc_day'] . ' ' . $data['bslc_from'] . ':' . '00' . ':00';
                                $time = strtotime($h_from);
                                $endTime = date("Y-m-d H:i:s", strtotime('+' . $jump . ' minutes', $time));

                                while ($data['bslc_from'] < $data['bslc_to']) {
                                    $wpdb->insert(
                                        $wpdb->prefix . 'special_booking',
                                        [
                                            'h_from' => $h_from,
                                            'h_to' => $endTime,
                                        ],
                                        [
                                            '%s',
                                            '%s',
                                        ]
                                    );

                                    $h_from = $endTime;
                                    if ($jump == 30) {
                                        $data['bslc_from'] = $data['bslc_from'] + 0.5;
                                    } else {
                                        $data['bslc_from'] = $data['bslc_from'] + 1;
                                    }
                                }

                                wp_redirect(admin_url('edit.php?post_type=special-calendar&page=special-calendar-settings&success=true'));
                                die;
                            }
                        } catch (Exception $e) {
                            $error = _x('Unknown date format.', 'booking-special-calendar');
                        }
                    } else {
                        $error = _x('Hours format illegal.', 'booking-special-calendar');
                    }
                } else {
                    $error = _x('All fields required.', 'booking-special-calendar');
                }
            }
        }

        if (is_file(plugin_dir_path(__FILE__) . 'includes/admin_dashboard.php')) {
            include_once plugin_dir_path(__FILE__) . 'includes/admin_dashboard.php';
        }
    }

    public static function assets()
    {

        /*My ;)*/
        wp_enqueue_style('bslc-bookings-css', plugin_dir_url(__FILE__) . 'public/css/bslc-style.css', null, self::$bslc_asset_version, 'all');
        wp_enqueue_script('bslc-bookings-js', plugin_dir_url(__FILE__) . 'public/js/calendar.js', ['bslc-iziToastjs'], self::$bslc_asset_version, true);

        /*iziToast*/
        wp_enqueue_style('bslc-iziToastcss', plugin_dir_url(__FILE__) . 'public/css/iziToast.css', self::$bslc_asset_version, null, 'all');
        wp_enqueue_script('bslc-iziToastjs', plugin_dir_url(__FILE__) . 'public/js/iziToast.js', self::$bslc_asset_version, true);

        /*datatable.net*/
        wp_enqueue_style('bslc-datatable-css', '//cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css', null, null, 'all');
        wp_enqueue_script('bslc-datatable-js', '//cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js', ['bslc-iziToastjs'], self::$bslc_asset_version, true);

        wp_localize_script('bslc-bookings-js', 'CALENDAR', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'fName' => _x('First Name', 'booking-special-calendar'),
            'lName' => _x('Last Name', 'booking-special-calendar'),
            'email' => _x('Email', 'booking-special-calendar'),
            'phone' => _x('Phone', 'booking-special-calendar'),
            'savingText' => _x('Processing...', 'booking-special-calendar'),
            'submitText' => _x('Send request.', 'booking-special-calendar'),
            'submitMeetingNonce' => wp_create_nonce('submitMeetingNonce'),
        ]);
    }

    public static function getCalendar()
    {
        ob_start();
        include_once plugin_dir_path(__FILE__) . 'includes/layout.php';

        return ob_get_clean();
    }

    public static function getCalendarHours()
    {
        $json = [];
        global $wpdb;
        if (isset($_POST['date']) && !empty($_POST['date']) && is_numeric($_POST['date'])) {
            $date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
            $date = date('Y-m-d', substr($date, 0, 10));
            if ($parsedDate = date_parse($date)) {
                $sql = "SELECT * FROM " . $wpdb->prefix . "special_booking WHERE YEAR(h_from) = %s AND MONTH(h_from) = %s GROUP BY h_from";
                $query = $wpdb->get_results($wpdb->prepare($sql, $parsedDate['year'], $parsedDate['month']));
                if (count($query) > 0) {
                    foreach ($query as $key => $value) {
                        $parsedValue = date_parse($value->h_from);
                        $json['success'][$parsedValue['year']][$parsedValue['month']][$parsedValue['day']][] = $query[$key];
                    }
                }
            } else {
                $json['error'] = _x('Unknown date format', 'booking-special-calendar');
            }
        }

        header('Content-Type: application/json');
        echo json_encode($json);
        die;
    }

    public static function updateDBCheck()
    {
        if (get_site_option(__CLASS__ . '::bslc_db_version') != self::$bslc_db_version) {
            self::install();
        }
    }

    public static function getBooking()
    {
        return get_option('bslc_booking_option_');
    }

    public static function install()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "special_booking";
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
                  id int (11) NOT NULL AUTO_INCREMENT,
                  post_id int (11) NOT NULL,
                  post_hourid int (11) NOT NULL,
                  h_from datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                  h_to datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                  meta_data text NOT NULL,
                  created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                  PRIMARY KEY  (id)
                ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option(__CLASS__ . '::bslc_db_version', self::$bslc_db_version);
    }

    public static function insertDemo()
    {
        global $wpdb;
        /*$from = date('Y-m-d 09:00:00', strtotime('+2 days'));

        $to = date('Y-m-d H:i:s', strtotime($from) + 60 * 60 * 1);
        for ($x = 1; $x <= 7; $x++) {
            $created_at = current_time('mysql');
            $meta_data = '';
            $table_name = $wpdb->prefix . 'special_booking';
            $insert = $wpdb->insert(
                $table_name,
                [
                    'created_at' => $created_at,
                    'h_from' => $from,
                    'h_to' => $to,
                    'meta_data' => $meta_data,
                ]
            );

            $from = $to;
            $to = date('Y-m-d H:i:s', strtotime($from) + 60 * 60 * 1);
        }*/
    }
}

BSLC_Booking::init();
BSLC_Booking::getBooking();
