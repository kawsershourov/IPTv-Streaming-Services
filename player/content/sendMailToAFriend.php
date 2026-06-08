<?php



require_once __DIR__ . '/../../../../wp-load.php';

// Verify nonce before processing request input.
$fwduvp_raw_nonce = filter_input(INPUT_GET, 'fwduvp_nonce', FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
$fwduvp_raw_nonce = $fwduvp_raw_nonce === null || $fwduvp_raw_nonce === false ? '' : (string) $fwduvp_raw_nonce;
$fwduvp_raw_nonce = wp_unslash($fwduvp_raw_nonce);
$fwduvp_nonce = sanitize_text_field($fwduvp_raw_nonce);

if ($fwduvp_nonce !== '' && !wp_verify_nonce($fwduvp_nonce, 'fwduvp_send_mail_to_friend')) {
	http_response_code(403);
	exit('Invalid nonce.');
}

// Read and sanitize input values from request.
$fwduvp_raw_to = filter_input(INPUT_GET, 'friendMail', FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
$fwduvp_raw_from = filter_input(INPUT_GET, 'yourMail', FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
$fwduvp_raw_link = filter_input(INPUT_GET, 'link', FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);

$fwduvp_raw_to = $fwduvp_raw_to === null || $fwduvp_raw_to === false ? '' : (string) $fwduvp_raw_to;
$fwduvp_raw_from = $fwduvp_raw_from === null || $fwduvp_raw_from === false ? '' : (string) $fwduvp_raw_from;
$fwduvp_raw_link = $fwduvp_raw_link === null || $fwduvp_raw_link === false ? '' : (string) $fwduvp_raw_link;

$fwduvp_to = sanitize_email(wp_unslash($fwduvp_raw_to));
$fwduvp_from = sanitize_email(wp_unslash($fwduvp_raw_from));
$fwduvp_link = esc_url_raw(wp_unslash($fwduvp_raw_link));

if (!is_email($fwduvp_to) || !is_email($fwduvp_from) || $fwduvp_link === '') {
	http_response_code(400);
	exit('Invalid parameters.');
}

$fwduvp_subject = 'Shared video from Ultimate Video Player';
$fwduvp_message = 'Your friend ' . $fwduvp_from . ' shared a video with you: ' . $fwduvp_link;
$fwduvp_headers = array(
	'Reply-To: ' . $fwduvp_from,
	'Content-Type: text/plain; charset=UTF-8',
);

if (!wp_mail($fwduvp_to, $fwduvp_subject, $fwduvp_message, $fwduvp_headers)) {
	echo 'error';
} else {
	echo 'sent';
}