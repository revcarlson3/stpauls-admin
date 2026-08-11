<?php
/**
 * Email sending wrapper for St. Paul's Admin plugin
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Get default from address/name
 * @return array [email, name]
 */
function spa_get_email_from() {
    $from = get_option('spa_notification_email', '');
    if (empty($from)) {
        $from = get_option('admin_email');
    }
    $from_name = get_option('spa_smtp_from_name', get_bloginfo('name'));
    return array($from, $from_name);
}

/**
 * Send email using selected provider
 * @param string|array $to
 * @param string $subject
 * @param string $message
 * @param array $headers
 * @param array $attachments
 * @return true|array|WP_Error
 */
function spa_send_email($to, $subject, $message, $headers = array(), $attachments = array(), $tracking = array()) {
    $provider = get_option('spa_email_provider', 'wp_mail');
    $subject = html_entity_decode((string) $subject, ENT_QUOTES, 'UTF-8');
    list($from_email, $from_name) = spa_get_email_from();

    // Ensure From header
    $headers = (array) $headers;
    $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
    $has_content_type = false;
    foreach ( $headers as $header ) {
        if ( stripos($header, 'Content-Type:') === 0 ) {
            $has_content_type = true;
            break;
        }
    }
    if ( ! $has_content_type ) {
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
    }

    switch ($provider) {
        case 'smtp':
            // Configure PHPMailer via phpmailer_init hook for this send
            add_action('phpmailer_init', function($phpmailer) {
                $phpmailer->isSMTP();
                $phpmailer->Host = get_option('spa_smtp_host', '');
                $phpmailer->Port = get_option('spa_smtp_port', 587);
                $phpmailer->SMTPAuth = true;
                $phpmailer->Username = get_option('spa_smtp_user', '');
                $phpmailer->Password = get_option('spa_smtp_pass', '');
                $enc = get_option('spa_smtp_encryption', 'tls');
                if ($enc === 'ssl') {
                    $phpmailer->SMTPSecure = 'ssl';
                } elseif ($enc === 'tls') {
                    $phpmailer->SMTPSecure = 'tls';
                }
            });

            $sent = wp_mail($to, $subject, $message, $headers, $attachments);
            // phpmailer_init hooked closure will be removed automatically at end of request scope
            return $sent ? true : new WP_Error('mail_failed', 'SMTP send failed');

        case 'sendgrid':
            $key = get_option('spa_sendgrid_api_key', '');
            if (empty($key)) return new WP_Error('no_key', 'SendGrid API key not configured');
            $personalization = array(
                'to' => array_map(function($r){ return array('email' => $r); }, (array)$to),
            );
            if ( ! empty($tracking['delivery_log_id']) ) {
                $personalization['custom_args'] = array(
                    'spa_log_id' => (string) intval($tracking['delivery_log_id']),
                );
            }
            $payload = array(
                'personalizations' => array($personalization),
                'from' => array('email' => $from_email, 'name' => $from_name),
                'subject' => $subject,
                'content' => array(array('type' => 'text/html', 'value' => $message))
            );
            $args = array(
                'headers' => array('Authorization' => 'Bearer ' . $key, 'Content-Type' => 'application/json'),
                'body' => wp_json_encode($payload),
                'timeout' => 20,
            );
            $resp = wp_remote_post('https://api.sendgrid.com/v3/mail/send', $args);
            if (is_wp_error($resp)) return $resp;
            $code = wp_remote_retrieve_response_code($resp);
            if ($code >= 200 && $code < 300) {
                if ( ! empty($tracking['delivery_log_id']) ) {
                    return array(
                        'message_id' => sanitize_text_field(wp_remote_retrieve_header($resp, 'x-message-id')),
                    );
                }
                return true;
            }
            return new WP_Error('sendgrid_error', 'SendGrid error: ' . wp_remote_retrieve_response_message($resp));

        case 'mailgun':
            $key = get_option('spa_mailgun_api_key', '');
            $domain = get_option('spa_mailgun_domain', '');
            if (empty($key) || empty($domain)) return new WP_Error('no_config', 'Mailgun not configured');
            $args = array(
                'body' => array(
                    'from' => $from_name . ' <' . $from_email . '>',
                    'to' => (is_array($to) ? implode(',', $to) : $to),
                    'subject' => $subject,
                    'html' => $message
                ),
                'timeout' => 20,
                'headers' => array(),
                'httpversion' => '1.1',
                'user' => 'api',
                'password' => $key
            );
            // Use basic auth via request args
            $resp = wp_remote_post('https://api.mailgun.net/v3/' . $domain . '/messages', $args);
            if (is_wp_error($resp)) return $resp;
            $code = wp_remote_retrieve_response_code($resp);
            if ($code >= 200 && $code < 300) return true;
            return new WP_Error('mailgun_error', 'Mailgun error: ' . wp_remote_retrieve_response_message($resp));

        case 'postmark':
            $token = get_option('spa_postmark_token', '');
            if (empty($token)) return new WP_Error('no_token', 'Postmark not configured');
            $args = array(
                'headers' => array('X-Postmark-Server-Token' => $token, 'Content-Type' => 'application/json'),
                'body' => wp_json_encode(array(
                    'From' => $from_email,
                    'To' => is_array($to) ? implode(',', $to) : $to,
                    'Subject' => $subject,
                    'HtmlBody' => $message
                )),
                'timeout' => 20
            );
            $resp = wp_remote_post('https://api.postmarkapp.com/email', $args);
            if (is_wp_error($resp)) return $resp;
            $code = wp_remote_retrieve_response_code($resp);
            if ($code >= 200 && $code < 300) return true;
            return new WP_Error('postmark_error', 'Postmark error: ' . wp_remote_retrieve_response_message($resp));

        case 'mailersend':
            $token = get_option('spa_mailersend_token', '');
            if (empty($token)) return new WP_Error('no_token', 'Mailersend not configured');
            $payload = array(
                'from' => array('email' => $from_email, 'name' => $from_name),
                'to' => array_map(function($r){ return array('email' => $r); }, (array)$to),
                'subject' => $subject,
                'html' => $message
            );
            $args = array(
                'headers' => array('Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json'),
                'body' => wp_json_encode($payload),
                'timeout' => 20
            );
            $resp = wp_remote_post('https://api.mailersend.com/v1/email', $args);
            if (is_wp_error($resp)) return $resp;
            $code = wp_remote_retrieve_response_code($resp);
            if ($code >= 200 && $code < 300) return true;
            return new WP_Error('mailersend_error', 'Mailersend error: ' . wp_remote_retrieve_response_message($resp));

        case 'mailpoet':
            // If MailPoet is active, hand off to its API when available. Otherwise fall back to wp_mail.
            if (class_exists('\MailPoet\API\Mailer\Mailer')) {
                try {
                    $mailer = \MailPoet\API\Mailer\Mailer::getInstance();
                    // MailPoet API expects specific structures; we'll call wp_mail as a fallback
                    return wp_mail($to, $subject, $message, $headers, $attachments) ? true : new WP_Error('mail_fail', 'MailPoet fallback failed');
                } catch (Exception $e) {
                    return new WP_Error('mailpoet_error', $e->getMessage());
                }
            }
            return wp_mail($to, $subject, $message, $headers, $attachments) ? true : new WP_Error('mail_fail', 'Send failed');

        case 'wp_mail':
        default:
            return wp_mail($to, $subject, $message, $headers, $attachments) ? true : new WP_Error('mail_fail', 'Send failed');
    }
}
