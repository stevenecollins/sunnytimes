<?php

/**
 * Ollie Child Theme Functions
 * The Sunroom Experts - Sunroom Calculator Integration
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

// ==============================================
// FORCE HTTPS FOR ALL CONTENT URLs (Fixes Mixed Content)
// ==============================================

add_filter('content_url', 'tse_force_https_urls', 10, 2);
add_filter('plugins_url', 'tse_force_https_urls', 10, 2);
add_filter('theme_root_uri', 'tse_force_https_urls', 10, 2);
add_filter('stylesheet_uri', 'tse_force_https_urls', 10, 2);
add_filter('template_directory_uri', 'tse_force_https_urls', 10, 2);

/**
 * Force HTTPS for all WordPress URLs
 */
function tse_force_https_urls($url)
{
	return str_replace('http://', 'https://', $url);
}

// ==============================================
// ORIGINAL CHILD THEME STYLES
// ==============================================

add_action('wp_enqueue_scripts', 'ollie_child_enqueue_styles');

/**
 * Enqueue Ollie styles.
 *
 * @return void
 */
function ollie_child_enqueue_styles(): void
{
	wp_enqueue_style('ollie-child-style', get_stylesheet_uri(), array('ollie', 'tse-google-fonts'), wp_get_theme()->get('Version'));
}

// ==============================================
// LOAD GOOGLE FONTS (Poppins & Inter)
// ==============================================

function tse_enqueue_google_fonts()
{
	// Load Poppins and Inter from Google Fonts
	wp_enqueue_style(
		'tse-google-fonts',
		'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;600;700&display=swap',
		array(),
		null
	);
}
add_action('wp_enqueue_scripts', 'tse_enqueue_google_fonts');

// ==============================================
// HERO SECTION - ENQUEUE STANDALONE CSS
// ==============================================

function tse_enqueue_hero_css()
{
	// Only load on homepage (adjust condition as needed)
	if (is_front_page() || is_home()) {
		wp_enqueue_style(
			'tse-hero-section',
			get_stylesheet_directory_uri() . '/assets/css/hero-section.css',
			array('ollie', 'tse-google-fonts'),
			'1.0.0', // Version for cache busting
			'all'
		);
	}
}
add_action('wp_enqueue_scripts', 'tse_enqueue_hero_css', 20); // Priority 20 to load after main styles

// ==============================================
// SUNROOM CALCULATOR - ENQUEUE ASSETS
// ==============================================

function tse_enqueue_calculator_assets()
{
	// Only load on calculator page
	if (is_page('get-your-quote')) {

		// Enqueue CSS
		wp_enqueue_style(
			'sunroom-calculator-css',
			get_stylesheet_directory_uri() . '/assets/css/sunroom-calculator.css',
			array('tse-google-fonts'), // Make sure fonts load first
			'1.0.1' // Increment version to bust cache
		);

		// Enqueue JavaScript
		wp_enqueue_script(
			'sunroom-calculator-js',
			get_stylesheet_directory_uri() . '/assets/js/sunroom-calculator.js',
			array('jquery'),
			'1.0.0',
			true
		);

		// Pass AJAX URL and nonce to JavaScript
		wp_localize_script('sunroom-calculator-js', 'tseCalculator', array(
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('tse_calculator_nonce'),
			'imageUrl' => get_stylesheet_directory_uri() . '/assets/images/',
		));
	}
}
add_action('wp_enqueue_scripts', 'tse_enqueue_calculator_assets');

// ==============================================
// CREATE DATABASE TABLE
// ==============================================

function tse_create_calculator_table()
{
	global $wpdb;

	$table_name = $wpdb->prefix . 'sunroom_quotes';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        quote_number varchar(20) NOT NULL,
        full_name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        phone varchar(50) NOT NULL,
        address text NOT NULL,
        city varchar(100) NOT NULL,
        state varchar(2) NOT NULL,
        zip varchar(10) NOT NULL,
        room_type varchar(50) NOT NULL,
        room_type_name varchar(100) NOT NULL,
        wall1_length decimal(10,2) NOT NULL,
        wall2_length decimal(10,2) NOT NULL,
        wall3_length decimal(10,2) NOT NULL,
        total_linear_feet decimal(10,2) NOT NULL,
        total_square_feet decimal(10,2) NOT NULL,
        price_min decimal(10,2) NOT NULL,
        price_max decimal(10,2) NOT NULL,
        project_details text,
        timeline text,
        user_agent text,
        ip_address varchar(100),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        status varchar(20) DEFAULT 'new',
        notes text,
        PRIMARY KEY  (id),
        KEY quote_number (quote_number),
        KEY email (email),
        KEY created_at (created_at),
        KEY status (status)
    ) $charset_collate;";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
}

// Run table creation on theme activation
add_action('after_switch_theme', 'tse_create_calculator_table');

// ==============================================
// HANDLE FORM SUBMISSION (AJAX)
// ==============================================

function tse_handle_calculator_submission()
{
	// Verify nonce for security
	if (!check_ajax_referer('tse_calculator_nonce', 'nonce', false)) {
		wp_send_json_error(array('message' => 'Security check failed'));
		return;
	}

	// Get and sanitize form data
	$data = array();

	// Contact Information
	$data['full_name'] = sanitize_text_field($_POST['fullName'] ?? '');
	$data['email'] = sanitize_email($_POST['email'] ?? '');
	$data['phone'] = sanitize_text_field($_POST['phone'] ?? '');
	$data['address'] = sanitize_text_field($_POST['address'] ?? '');
	$data['city'] = sanitize_text_field($_POST['city'] ?? '');
	$data['state'] = sanitize_text_field($_POST['state'] ?? '');
	$data['zip'] = sanitize_text_field($_POST['zip'] ?? '');

	// Room Information
	$data['room_type'] = sanitize_text_field($_POST['roomType'] ?? '');
	$data['room_type_name'] = sanitize_text_field($_POST['roomTypeName'] ?? '');
	$data['wall1_length'] = floatval($_POST['wall1'] ?? 0);
	$data['wall2_length'] = floatval($_POST['wall2'] ?? 0);
	$data['wall3_length'] = floatval($_POST['wall3'] ?? 0);
	$data['total_linear_feet'] = floatval($_POST['totalLinearFeet'] ?? 0);
	$data['total_square_feet'] = floatval($_POST['totalSquareFeet'] ?? 0);
	$data['price_min'] = floatval($_POST['priceMin'] ?? 0);
	$data['price_max'] = floatval($_POST['priceMax'] ?? 0);

	// Project Details
	$data['project_details'] = sanitize_textarea_field($_POST['projectDetails'] ?? '');

	// Metadata
	$data['user_agent'] = sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? '');
	$data['ip_address'] = tse_get_client_ip();

	// Handle file upload
	$data['porch_image_path'] = '';
	$data['porch_image_filename'] = '';

	if (!empty($_FILES['porchImage']) && $_FILES['porchImage']['error'] === UPLOAD_ERR_OK) {
		// Validate file type
		$allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/heic');
		$file_type = $_FILES['porchImage']['type'];

		if (in_array($file_type, $allowed_types)) {
			// Validate file size (10MB max)
			if ($_FILES['porchImage']['size'] <= 10 * 1024 * 1024) {
				// Use WordPress upload handler
				require_once(ABSPATH . 'wp-admin/includes/file.php');

				$upload_overrides = array(
					'test_form' => false,
					'mimes' => array(
						'jpg|jpeg|jpe' => 'image/jpeg',
						'png' => 'image/png',
						'heic' => 'image/heic'
					)
				);

				$movefile = wp_handle_upload($_FILES['porchImage'], $upload_overrides);

				if ($movefile && !isset($movefile['error'])) {
					$data['porch_image_path'] = $movefile['url'];
					$data['porch_image_filename'] = basename($movefile['file']);
				}
			}
		}
	}

	// Validate required fields
	$required_fields = array('full_name', 'email', 'phone', 'address', 'city', 'state', 'zip');
	foreach ($required_fields as $field) {
		if (empty($data[$field])) {
			wp_send_json_error(array('message' => "Missing required field: $field"));
			return;
		}
	}

	// Validate email format
	if (!is_email($data['email'])) {
		wp_send_json_error(array('message' => 'Invalid email address'));
		return;
	}

	// Generate unique quote number
	$data['quote_number'] = tse_generate_quote_number();

	// Insert into database
	global $wpdb;
	$table_name = $wpdb->prefix . 'sunroom_quotes';

	$inserted = $wpdb->insert(
		$table_name,
		$data,
		array(
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s', // contact info
			'%s',
			'%s',
			'%f',
			'%f',
			'%f',
			'%f',
			'%f',
			'%f',
			'%f', // room info
			'%s',
			'%s', // project details
			'%s',
			'%s',
			'%s' // metadata
		)
	);

	if ($inserted === false) {
		wp_send_json_error(array('message' => 'Database error: ' . $wpdb->last_error));
		return;
	}

	// Send email notification
	$email_sent = tse_send_quote_email($data);

	// Return success response
	wp_send_json_success(array(
		'message' => 'Quote submitted successfully!',
		'quote_number' => $data['quote_number'],
		'email_sent' => $email_sent
	));
}

// Register AJAX handlers (for logged in and non-logged in users)
add_action('wp_ajax_tse_submit_calculator', 'tse_handle_calculator_submission');
add_action('wp_ajax_nopriv_tse_submit_calculator', 'tse_handle_calculator_submission');

// ==============================================
// AJAX HANDLER - GET QUOTE DETAILS
// ==============================================

/**
 * AJAX handler to fetch single quote details for modal
 */
function tse_get_quote_details_ajax()
{
	// Verify nonce
	if (!check_ajax_referer('tse_quote_details_nonce', 'nonce', false)) {
		wp_send_json_error(array('message' => 'Security check failed'));
		return;
	}

	// Check user capabilities
	if (!current_user_can('manage_options')) {
		wp_send_json_error(array('message' => 'Insufficient permissions'));
		return;
	}

	// Get quote ID
	$quote_id = intval($_POST['quote_id'] ?? 0);
	if ($quote_id <= 0) {
		wp_send_json_error(array('message' => 'Invalid quote ID'));
		return;
	}

	// Fetch quote from database
	global $wpdb;
	$table_name = $wpdb->prefix . 'sunroom_quotes';
	$quote = $wpdb->get_row($wpdb->prepare(
		"SELECT * FROM $table_name WHERE id = %d",
		$quote_id
	));

	if (!$quote) {
		wp_send_json_error(array('message' => 'Quote not found'));
		return;
	}

	// Return quote data as JSON
	wp_send_json_success(array('quote' => $quote));
}

// Register AJAX action for quote details
add_action('wp_ajax_tse_get_quote_details', 'tse_get_quote_details_ajax');

// ==============================================
// AJAX HANDLER - ADD NOTE TO QUOTE
// ==============================================

/**
 * AJAX handler to add a note to a quote
 */
function tse_add_quote_note_ajax()
{
	// Verify nonce
	if (!check_ajax_referer('tse_quote_notes_nonce', 'nonce', false)) {
		wp_send_json_error(array('message' => 'Security check failed'));
		return;
	}

	// Check user capabilities
	if (!current_user_can('manage_options')) {
		wp_send_json_error(array('message' => 'Insufficient permissions'));
		return;
	}

	// Get parameters
	$quote_id = intval($_POST['quote_id'] ?? 0);
	$note_content = sanitize_textarea_field($_POST['note_content'] ?? '');

	if ($quote_id <= 0) {
		wp_send_json_error(array('message' => 'Invalid quote ID'));
		return;
	}

	if (empty(trim($note_content))) {
		wp_send_json_error(array('message' => 'Note content cannot be empty'));
		return;
	}

	// Get current user info
	$current_user = wp_get_current_user();
	$author_name = !empty($current_user->first_name) ? $current_user->first_name : $current_user->user_login;

	// Fetch current notes from database
	global $wpdb;
	$table_name = $wpdb->prefix . 'sunroom_quotes';
	$quote = $wpdb->get_row($wpdb->prepare(
		"SELECT notes FROM $table_name WHERE id = %d",
		$quote_id
	));

	if (!$quote) {
		wp_send_json_error(array('message' => 'Quote not found'));
		return;
	}

	// Decode existing notes
	$notes = array();
	if (!empty($quote->notes)) {
		$notes = json_decode($quote->notes, true);
		if (!is_array($notes)) {
			$notes = array();
		}
	}

	// Create new note
	$new_note = array(
		'content' => $note_content,
		'author' => $author_name,
		'timestamp' => current_time('mysql')
	);

	// Add new note to beginning (newest first)
	array_unshift($notes, $new_note);

	// Save back to database
	$updated = $wpdb->update(
		$table_name,
		array('notes' => json_encode($notes)),
		array('id' => $quote_id),
		array('%s'),
		array('%d')
	);

	if ($updated === false) {
		wp_send_json_error(array('message' => 'Failed to save note'));
		return;
	}

	// Return success with updated notes
	wp_send_json_success(array(
		'message' => 'Note added successfully',
		'notes' => $notes
	));
}

// Register AJAX action for adding notes
add_action('wp_ajax_tse_add_quote_note', 'tse_add_quote_note_ajax');

// ==============================================
// AJAX HANDLER - ARCHIVE/RESTORE QUOTE
// ==============================================

/**
 * AJAX handler to archive or restore a quote
 */
function tse_archive_quote_ajax()
{
	// Verify nonce
	if (!check_ajax_referer('tse_archive_nonce', 'nonce', false)) {
		wp_send_json_error(array('message' => 'Security check failed'));
		return;
	}

	// Check user capabilities
	if (!current_user_can('manage_options')) {
		wp_send_json_error(array('message' => 'Insufficient permissions'));
		return;
	}

	// Get parameters
	$quote_id = intval($_POST['quote_id'] ?? 0);
	$action_type = sanitize_text_field($_POST['action_type'] ?? ''); // 'archive' or 'restore'

	if ($quote_id <= 0) {
		wp_send_json_error(array('message' => 'Invalid quote ID'));
		return;
	}

	if (!in_array($action_type, array('archive', 'restore'))) {
		wp_send_json_error(array('message' => 'Invalid action type'));
		return;
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'sunroom_quotes';

	// Get current quote data
	$quote = $wpdb->get_row($wpdb->prepare(
		"SELECT id, status FROM $table_name WHERE id = %d",
		$quote_id
	));

	if (!$quote) {
		wp_send_json_error(array('message' => 'Quote not found'));
		return;
	}

	// Determine new status
	if ($action_type === 'archive') {
		$new_status = 'archived';
	} else {
		// Restore to 'new' status
		$new_status = 'new';
	}

	// Update status
	$updated = $wpdb->update(
		$table_name,
		array('status' => $new_status),
		array('id' => $quote_id),
		array('%s'),
		array('%d')
	);

	if ($updated === false) {
		wp_send_json_error(array('message' => 'Failed to update quote status'));
		return;
	}

	// Return success
	wp_send_json_success(array(
		'message' => $action_type === 'archive' ? 'Quote archived successfully' : 'Quote restored successfully',
		'new_status' => $new_status
	));
}

// Register AJAX action for archive/restore
add_action('wp_ajax_tse_archive_quote', 'tse_archive_quote_ajax');

// ==============================================
// AJAX HANDLER - QUICK EDIT STATUS
// ==============================================

/**
 * AJAX handler for quick status update
 */
function tse_quick_edit_status_ajax()
{
	// Verify nonce
	if (!check_ajax_referer('tse_quick_edit_nonce', 'nonce', false)) {
		wp_send_json_error(array('message' => 'Security check failed'));
		return;
	}

	// Check user capabilities
	if (!current_user_can('manage_options')) {
		wp_send_json_error(array('message' => 'Insufficient permissions'));
		return;
	}

	// Get parameters
	$quote_id = intval($_POST['quote_id'] ?? 0);
	$new_status = sanitize_text_field($_POST['status'] ?? '');

	if ($quote_id <= 0) {
		wp_send_json_error(array('message' => 'Invalid quote ID'));
		return;
	}

	// Validate status
	$valid_statuses = array('new', 'contacted', 'scheduled', 'quoted', 'won', 'lost', 'archived');
	if (!in_array($new_status, $valid_statuses)) {
		wp_send_json_error(array('message' => 'Invalid status value'));
		return;
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'sunroom_quotes';

	// Update status
	$updated = $wpdb->update(
		$table_name,
		array('status' => $new_status),
		array('id' => $quote_id),
		array('%s'),
		array('%d')
	);

	if ($updated === false) {
		wp_send_json_error(array('message' => 'Failed to update status'));
		return;
	}

	// Return success
	wp_send_json_success(array(
		'message' => 'Status updated successfully',
		'new_status' => $new_status
	));
}

// Register AJAX action for quick edit
add_action('wp_ajax_tse_quick_edit_status', 'tse_quick_edit_status_ajax');

// ==============================================
// HELPER FUNCTIONS
// ==============================================

/**
 * Generate unique quote number
 */
function tse_generate_quote_number()
{
	$prefix = 'TSE';
	$date = date('Ymd');
	$random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
	return $prefix . '-' . $date . '-' . $random;
}

/**
 * Get client IP address
 */
function tse_get_client_ip()
{
	$ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
	foreach ($ip_keys as $key) {
		if (array_key_exists($key, $_SERVER) === true) {
			foreach (explode(',', $_SERVER[$key]) as $ip) {
				$ip = trim($ip);
				if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
					return $ip;
				}
			}
		}
	}
	return 'Unknown';
}

// ==============================================
// EMAIL NOTIFICATION
// ==============================================

/**
 * Send email notification when quote is submitted
 */
function tse_send_quote_email($data)
{
	// Sales notification email
	$to = 'caison@thesunroomexperts.com';

	$subject = sprintf(
		'New Sunroom Quote Request - %s - %s',
		$data['quote_number'],
		$data['full_name']
	);

	// Create email body
	$message = tse_get_email_template($data);

	// Email headers
	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		'From: The Sunroom Experts <noreply@thesunroomexperts.com>',
		'Reply-To: ' . $data['full_name'] . ' <' . $data['email'] . '>'
	);

	// Send email
	$sent = wp_mail($to, $subject, $message, $headers);

	// Optional: Send confirmation email to customer
	if ($sent) {
		tse_send_customer_confirmation_email($data);
	}

	return $sent;
}

/**
 * Email template for sales notification
 */
function tse_get_email_template($data)
{
	ob_start();
?>
	<!DOCTYPE html>
	<html>

	<head>
		<meta charset="UTF-8">
		<style>
			body {
				font-family: Arial, sans-serif;
				line-height: 1.6;
				color: #333;
			}

			.container {
				max-width: 600px;
				margin: 0 auto;
				padding: 20px;
			}

			.header {
				background: #255E65;
				color: white;
				padding: 20px;
				text-align: center;
			}

			.content {
				background: #f9f9f9;
				padding: 20px;
			}

			.section {
				background: white;
				margin: 20px 0;
				padding: 15px;
				border-left: 4px solid #255E65;
			}

			.section h3 {
				margin-top: 0;
				color: #255E65;
			}

			.info-row {
				margin: 10px 0;
			}

			.label {
				font-weight: bold;
				color: #666;
			}

			.value {
				color: #333;
			}

			.price-box {
				background: #E8F4F5;
				padding: 15px;
				text-align: center;
				margin: 20px 0;
				border-radius: 5px;
			}

			.price-box .amount {
				font-size: 24px;
				font-weight: bold;
				color: #255E65;
			}

			.footer {
				text-align: center;
				padding: 20px;
				color: #666;
				font-size: 12px;
			}
		</style>
	</head>

	<body>
		<div class="container">
			<div class="header">
				<h1>🏠 New Sunroom Quote Request</h1>
				<p>Quote #: <?php echo esc_html($data['quote_number']); ?></p>
			</div>

			<div class="content">
				<!-- Price Estimate -->
				<div class="price-box">
					<div class="label">Estimated Price Range</div>
					<div class="amount">
						$<?php echo number_format($data['price_min'], 0); ?> - $<?php echo number_format($data['price_max'], 0); ?>
					</div>
					<div style="margin-top: 10px; color: #666;">
						<?php echo esc_html($data['room_type_name']); ?> |
						<?php echo number_format($data['total_linear_feet'], 1); ?> Linear Feet |
						<?php echo number_format($data['total_square_feet'], 0); ?> Square Feet
					</div>
				</div>

				<!-- Customer Information -->
				<div class="section">
					<h3>📋 Customer Information</h3>
					<div class="info-row">
						<span class="label">Name:</span>
						<span class="value"><?php echo esc_html($data['full_name']); ?></span>
					</div>
					<div class="info-row">
						<span class="label">Email:</span>
						<span class="value"><a href="mailto:<?php echo esc_attr($data['email']); ?>"><?php echo esc_html($data['email']); ?></a></span>
					</div>
					<div class="info-row">
						<span class="label">Phone:</span>
						<span class="value"><a href="tel:<?php echo esc_attr($data['phone']); ?>"><?php echo esc_html($data['phone']); ?></a></span>
					</div>
					<div class="info-row">
						<span class="label">Address:</span>
						<span class="value">
							<?php echo esc_html($data['address']); ?><br>
							<?php echo esc_html($data['city']); ?>, <?php echo esc_html($data['state']); ?> <?php echo esc_html($data['zip']); ?>
						</span>
					</div>
				</div>

				<!-- Sunroom Details -->
				<div class="section">
					<h3>🏡 Sunroom Details</h3>
					<div class="info-row">
						<span class="label">Room Type:</span>
						<span class="value"><?php echo esc_html($data['room_type_name']); ?></span>
					</div>
					<div class="info-row">
						<span class="label">Dimensions:</span>
						<span class="value">
							Wall 1: <?php echo number_format($data['wall1_length'], 1); ?> ft<br>
							Wall 2: <?php echo number_format($data['wall2_length'], 1); ?> ft<br>
							Wall 3: <?php echo number_format($data['wall3_length'], 1); ?> ft<br>
							<strong>Total: <?php echo number_format($data['total_linear_feet'], 1); ?> linear feet (<?php echo number_format($data['total_square_feet'], 0); ?> sq ft)</strong>
						</span>
					</div>
				</div>

				<!-- Project Details -->
				<?php if (!empty($data['project_details']) || !empty($data['timeline'])): ?>
					<div class="section">
						<h3>📝 Project Details</h3>
						<?php if (!empty($data['project_details'])): ?>
							<div class="info-row">
								<span class="label">Description:</span><br>
								<span class="value"><?php echo nl2br(esc_html($data['project_details'])); ?></span>
							</div>
						<?php endif; ?>
						<?php if (!empty($data['porch_image_path'])): ?>
							<div class="info-row">
								<span class="label">📷 Porch Photo:</span><br>
								<span class="value">
									<a href="<?php echo esc_url($data['porch_image_path']); ?>" target="_blank" style="color: #255E65; font-weight: bold;">
										🔗 View Porch Image (<?php echo esc_html($data['porch_image_filename']); ?>)
									</a>
								</span>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<!-- Call to Action -->
				<div style="text-align: center; margin: 30px 0;">
					<p><strong>⏰ Next Step: Contact this customer within 24 hours!</strong></p>
					<p>
						<a href="tel:<?php echo esc_attr($data['phone']); ?>"
							style="display: inline-block; background: #255E65; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 5px;">
							📞 Call Now
						</a>
						<a href="mailto:<?php echo esc_attr($data['email']); ?>"
							style="display: inline-block; background: #85A3A3; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 5px;">
							✉️ Send Email
						</a>
					</p>
				</div>
			</div>

			<div class="footer">
				<p>Submitted: <?php echo date('F j, Y \a\t g:i A'); ?></p>
				<p>IP Address: <?php echo esc_html($data['ip_address']); ?></p>
			</div>
		</div>
	</body>

	</html>
<?php
	return ob_get_clean();
}

/**
 * Send confirmation email to customer
 */
function tse_send_customer_confirmation_email($data)
{
	$to = $data['email'];
	$subject = 'Your Sunroom Quote Request - ' . $data['quote_number'];

	ob_start();
?>
	<!DOCTYPE html>
	<html>

	<head>
		<meta charset="UTF-8">
		<style>
			body {
				font-family: Arial, sans-serif;
				line-height: 1.6;
				color: #333;
			}

			.container {
				max-width: 600px;
				margin: 0 auto;
				padding: 20px;
			}

			.header {
				background: #255E65;
				color: white;
				padding: 20px;
				text-align: center;
			}

			.content {
				padding: 20px;
			}
		</style>
	</head>

	<body>
		<div class="container">
			<div class="header">
				<h1>Thank You, <?php echo esc_html($data['full_name']); ?>!</h1>
				<p>Your quote request has been received</p>
			</div>

			<div class="content">
				<p><strong>Quote Number:</strong> <?php echo esc_html($data['quote_number']); ?></p>

				<p>Thank you for your interest in a <?php echo esc_html($data['room_type_name']); ?>!</p>

				<p><strong>What happens next:</strong></p>
				<ul>
					<li>One of our sunroom specialists will contact you within 24 hours</li>
					<li>We'll discuss your vision and answer any questions</li>
					<li>We'll schedule a convenient time for your free in-home consultation</li>
				</ul>

				<p><strong>Your Estimated Price Range:</strong><br>
					$<?php echo number_format($data['price_min'], 0); ?> - $<?php echo number_format($data['price_max'], 0); ?></p>

				<p>If you have any immediate questions, please don't hesitate to call us at <strong>(910) 555-0000</strong>.</p>

				<p>We look forward to bringing your sunroom dreams to life!</p>

				<p>Best regards,<br>
					<strong>The Sunroom Experts Team</strong>
				</p>
			</div>
		</div>
	</body>

	</html>
<?php
	$message = ob_get_clean();

	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		'From: The Sunroom Experts <noreply@thesunroomexperts.com>'
	);

	wp_mail($to, $subject, $message, $headers);
}

// ==============================================
// ADMIN MENU FOR VIEWING QUOTES
// ==============================================

/**
 * Add admin menu for viewing quotes
 */
function tse_add_admin_menu()
{
	add_menu_page(
		'Sunroom Quotes',
		'Sunroom Quotes',
		'manage_options',
		'sunroom-quotes',
		'tse_quotes_admin_page',
		'dashicons-clipboard',
		30
	);
}
add_action('admin_menu', 'tse_add_admin_menu');

/**
 * Admin page for viewing quotes
 */
function tse_quotes_admin_page()
{
	global $wpdb;
	$table_name = $wpdb->prefix . 'sunroom_quotes';

	// Determine which view (active or archived)
	$view = isset($_GET['view']) && $_GET['view'] === 'archived' ? 'archived' : 'active';

	// Get quotes based on view
	if ($view === 'archived') {
		$quotes = $wpdb->get_results("SELECT * FROM $table_name WHERE status = 'archived' ORDER BY created_at DESC");
	} else {
		$quotes = $wpdb->get_results("SELECT * FROM $table_name WHERE status != 'archived' ORDER BY created_at DESC");
	}

	// Count quotes for each view
	$active_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status != 'archived'");
	$archived_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'archived'");

?>
	<div class="wrap">
		<h1>Sunroom Quote Requests</h1>

		<!-- View Toggle Buttons -->
		<div class="tse-view-toggle" style="margin: 20px 0;">
			<a href="?page=sunroom-quotes&view=active"
				class="button <?php echo $view === 'active' ? 'button-primary' : ''; ?>">
				📋 Active Quotes (<?php echo $active_count; ?>)
			</a>
			<a href="?page=sunroom-quotes&view=archived"
				class="button <?php echo $view === 'archived' ? 'button-primary' : ''; ?>">
				🗄️ Archived (<?php echo $archived_count; ?>)
			</a>
		</div>

		<!-- Modal Overlay -->
		<div id="tse-quote-modal-overlay" class="tse-modal-overlay" style="display:none;">
			<div class="tse-modal-content">
				<div class="tse-modal-header">
					<h2 id="tse-modal-title">Quote Details</h2>
					<button class="tse-modal-close" title="Close">&times;</button>
				</div>
				<div class="tse-modal-body" id="tse-modal-body">
					<div class="tse-loading">Loading quote details...</div>
				</div>
			</div>
		</div>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th>Quote #</th>
					<th>Date</th>
					<th>Customer</th>
					<th>Contact</th>
					<th>Room Type</th>
					<th>Price Range</th>
					<th>Status</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($quotes)): ?>
					<tr>
						<td colspan="8" style="text-align: center; padding: 40px;">
							<p>No quotes yet. Test the calculator to see submissions here!</p>
						</td>
					</tr>
				<?php else: ?>
					<?php foreach ($quotes as $quote): ?>
						<tr<?php echo $quote->status === 'archived' ? ' class="archived-row"' : ''; ?>>
							<td><strong><?php echo esc_html($quote->quote_number); ?></strong></td>
							<td><?php echo date('M j, Y', strtotime($quote->created_at)); ?></td>
							<td>
								<strong><?php echo esc_html($quote->full_name); ?></strong><br>
								<small><?php echo esc_html($quote->city); ?>, <?php echo esc_html($quote->state); ?></small>
							</td>
							<td>
								<a href="mailto:<?php echo esc_attr($quote->email); ?>"><?php echo esc_html($quote->email); ?></a><br>
								<a href="tel:<?php echo esc_attr($quote->phone); ?>"><?php echo esc_html($quote->phone); ?></a>
							</td>
							<td>
								<?php echo esc_html($quote->room_type_name); ?><br>
								<small><?php echo number_format($quote->total_linear_feet, 1); ?> ft</small>
							</td>
							<td>
								$<?php echo number_format($quote->price_min, 0); ?> -
								$<?php echo number_format($quote->price_max, 0); ?>
							</td>
							<td>
								<div class="tse-status-cell">
									<select class="tse-status-dropdown" data-quote-id="<?php echo $quote->id; ?>">
										<option value="new" <?php selected($quote->status, 'new'); ?>>New</option>
										<option value="contacted" <?php selected($quote->status, 'contacted'); ?>>Contacted</option>
										<option value="scheduled" <?php selected($quote->status, 'scheduled'); ?>>Scheduled</option>
										<option value="quoted" <?php selected($quote->status, 'quoted'); ?>>Quoted</option>
										<option value="won" <?php selected($quote->status, 'won'); ?>>Won</option>
										<option value="lost" <?php selected($quote->status, 'lost'); ?>>Lost</option>
									</select>
									<span class="tse-status-indicator" style="display:none; margin-left: 8px; font-size: 12px;"></span>
								</div>
							</td>
							<td>
								<button class="button button-small tse-view-details" data-quote-id="<?php echo esc_attr($quote->id); ?>">View Details</button>
								<?php if ($quote->status === 'archived'): ?>
									<button class="button button-small tse-restore-quote" data-quote-id="<?php echo esc_attr($quote->id); ?>"
										style="color: #2271b1;">
										↻ Restore
									</button>
								<?php else: ?>
									<button class="button button-small tse-archive-quote" data-quote-id="<?php echo esc_attr($quote->id); ?>"
										style="color: #d63638;">
										🗄️ Archive
									</button>
								<?php endif; ?>
							</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
			</tbody>
		</table>
	</div>

	<!-- Modal Styles -->
	<style>
		/* Modal Overlay */
		.tse-modal-overlay {
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: rgba(0, 0, 0, 0.7);
			z-index: 100000;
			display: flex;
			align-items: center;
			justify-content: center;
			animation: tse-fade-in 0.2s ease;
		}

		@keyframes tse-fade-in {
			from {
				opacity: 0;
			}

			to {
				opacity: 1;
			}
		}

		/* Modal Content Box */
		.tse-modal-content {
			background: #fff;
			width: 90%;
			max-width: 900px;
			max-height: 90vh;
			border-radius: 8px;
			box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
			display: flex;
			flex-direction: column;
			animation: tse-slide-up 0.3s ease;
		}

		@keyframes tse-slide-up {
			from {
				transform: translateY(30px);
				opacity: 0;
			}

			to {
				transform: translateY(0);
				opacity: 1;
			}
		}

		/* Modal Header */
		.tse-modal-header {
			background: #255E65;
			color: #fff;
			padding: 20px 30px;
			border-radius: 8px 8px 0 0;
			display: flex;
			justify-content: space-between;
			align-items: center;
			flex-shrink: 0;
		}

		.tse-modal-header h2 {
			margin: 0;
			font-size: 22px;
			color: #fff;
		}

		.tse-modal-close {
			background: transparent;
			border: none;
			color: #fff;
			font-size: 32px;
			line-height: 1;
			cursor: pointer;
			padding: 0;
			width: 32px;
			height: 32px;
			transition: opacity 0.2s;
		}

		.tse-modal-close:hover {
			opacity: 0.7;
		}

		/* Modal Body */
		.tse-modal-body {
			padding: 30px;
			overflow-y: auto;
			flex: 1;
		}

		.tse-loading {
			text-align: center;
			padding: 40px;
			color: #666;
			font-size: 16px;
		}

		/* Quote Details Sections */
		.tse-quote-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 30px;
			padding-bottom: 20px;
			border-bottom: 2px solid #e5e5e5;
			flex-wrap: wrap;
			gap: 15px;
		}

		.tse-quote-number {
			font-size: 24px;
			font-weight: bold;
			color: #255E65;
		}

		.tse-quote-meta {
			display: flex;
			gap: 15px;
			align-items: center;
			flex-wrap: wrap;
		}

		.tse-status-badge {
			padding: 6px 16px;
			border-radius: 20px;
			font-size: 13px;
			font-weight: 600;
			text-transform: uppercase;
			letter-spacing: 0.5px;
		}

		.tse-status-new {
			background: #e3f2fd;
			color: #1976d2;
		}

		.tse-status-contacted {
			background: #fff3e0;
			color: #f57c00;
		}

		.tse-status-scheduled {
			background: #f3e5f5;
			color: #7b1fa2;
		}

		.tse-status-quoted {
			background: #e8f5e9;
			color: #388e3c;
		}

		.tse-status-won {
			background: #c8e6c9;
			color: #2e7d32;
		}

		.tse-status-lost {
			background: #ffcdd2;
			color: #c62828;
		}

		.tse-quote-date {
			color: #666;
			font-size: 14px;
		}

		.tse-detail-section {
			margin-bottom: 30px;
		}

		.tse-section-title {
			font-size: 18px;
			font-weight: 600;
			color: #255E65;
			margin-bottom: 15px;
			padding-bottom: 8px;
			border-bottom: 1px solid #e5e5e5;
		}

		.tse-info-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
			gap: 20px;
		}

		.tse-info-item {
			margin-bottom: 12px;
		}

		.tse-info-label {
			font-weight: 600;
			color: #666;
			font-size: 13px;
			text-transform: uppercase;
			letter-spacing: 0.5px;
			margin-bottom: 4px;
		}

		.tse-info-value {
			color: #333;
			font-size: 15px;
		}

		.tse-info-value a {
			color: #255E65;
			text-decoration: none;
		}

		.tse-info-value a:hover {
			text-decoration: underline;
		}

		/* Pricing Box */
		.tse-pricing-box {
			background: linear-gradient(135deg, #255E65 0%, #85A3A3 100%);
			color: #fff;
			padding: 25px;
			border-radius: 8px;
			text-align: center;
			margin: 20px 0;
		}

		.tse-pricing-label {
			font-size: 14px;
			opacity: 0.9;
			margin-bottom: 8px;
		}

		.tse-pricing-amount {
			font-size: 32px;
			font-weight: bold;
		}

		.tse-pricing-details {
			margin-top: 12px;
			font-size: 14px;
			opacity: 0.9;
		}

		/* Measurements Table */
		.tse-measurements-table {
			background: #f9f9f9;
			padding: 20px;
			border-radius: 8px;
			margin: 15px 0;
		}

		.tse-measurement-row {
			display: flex;
			justify-content: space-between;
			padding: 10px 0;
			border-bottom: 1px solid #e5e5e5;
		}

		.tse-measurement-row:last-child {
			border-bottom: none;
			margin-top: 8px;
			padding-top: 18px;
			border-top: 2px solid #255E65;
			font-weight: bold;
		}

		.tse-measurement-label {
			color: #666;
			font-size: 14px;
		}

		.tse-measurement-value {
			color: #255E65;
			font-weight: 600;
			font-size: 14px;
		}

		/* Project Details */
		.tse-project-text {
			background: #f9f9f9;
			padding: 15px;
			border-radius: 6px;
			color: #333;
			line-height: 1.6;
			white-space: pre-wrap;
		}

		/* Internal Notes Section */
		.tse-notes-section {
			margin-top: 20px;
		}

		.tse-notes-list {
			margin: 15px 0 20px 0;
			max-height: 300px;
			overflow-y: auto;
		}

		.tse-note-item {
			background: #fff;
			border-left: 3px solid #255E65;
			padding: 12px 15px;
			margin-bottom: 12px;
			border-radius: 4px;
			box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
		}

		.tse-note-content {
			color: #333;
			line-height: 1.5;
			margin-bottom: 8px;
			white-space: pre-wrap;
		}

		.tse-note-meta {
			display: flex;
			gap: 10px;
			font-size: 12px;
			color: #999;
		}

		.tse-note-author {
			font-weight: 600;
			color: #255E65;
		}

		.tse-note-time {
			color: #999;
		}

		.tse-notes-empty {
			text-align: center;
			padding: 30px;
			color: #999;
			background: #f9f9f9;
			border-radius: 6px;
			font-style: italic;
		}

		/* Add Note Form */
		.tse-add-note-form {
			margin-top: 15px;
			padding: 15px;
			background: #f9f9f9;
			border-radius: 6px;
		}

		.tse-add-note-form textarea {
			width: 100%;
			min-height: 80px;
			padding: 10px;
			border: 1px solid #ddd;
			border-radius: 4px;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, sans-serif;
			font-size: 14px;
			resize: vertical;
			margin-bottom: 10px;
		}

		.tse-add-note-form textarea:focus {
			border-color: #255E65;
			outline: none;
			box-shadow: 0 0 0 1px #255E65;
		}

		.tse-add-note-btn {
			background: #255E65;
			color: white;
			border: none;
			padding: 10px 20px;
			border-radius: 4px;
			cursor: pointer;
			font-size: 14px;
			font-weight: 600;
			transition: background 0.2s;
		}

		.tse-add-note-btn:hover {
			background: #1a4750;
		}

		.tse-add-note-btn:disabled {
			background: #ccc;
			cursor: not-allowed;
		}

		.tse-note-success,
		.tse-note-error {
			padding: 8px 12px;
			border-radius: 4px;
			margin-top: 10px;
			font-size: 13px;
		}

		.tse-note-success {
			background: #e8f5e9;
			color: #2e7d32;
			border-left: 3px solid #2e7d32;
		}

		.tse-note-error {
			background: #ffebee;
			color: #c62828;
			border-left: 3px solid #c62828;
		}

		/* Metadata Footer */
		.tse-metadata {
			background: #f5f5f5;
			padding: 15px;
			border-radius: 6px;
			margin-top: 20px;
		}

		.tse-metadata-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
			gap: 15px;
		}

		.tse-metadata .tse-info-label,
		.tse-metadata .tse-info-value {
			font-size: 12px;
		}

		/* Archived Quote Styling */
		.tse-status-archived {
			background: #f0f0f0;
			color: #666;
		}

		tr.archived-row {
			opacity: 0.6;
			background: #f9f9f9;
		}

		tr.archived-row td {
			color: #999;
		}

		/* Archive/Restore Buttons */
		.tse-archive-quote,
		.tse-restore-quote {
			margin-left: 5px;
		}

		.tse-archive-quote:hover {
			color: #a00 !important;
		}

		.tse-restore-quote:hover {
			color: #135e96 !important;
		}

		/* Quick Edit Status */
		.tse-status-cell {
			display: flex;
			align-items: center;
		}

		.tse-status-dropdown {
			min-width: 110px;
		}

		.tse-status-indicator {
			font-weight: 600;
			white-space: nowrap;
		}

		.tse-status-saving {
			color: #f0b849;
		}

		.tse-status-saved {
			color: #46b450;
		}

		.tse-status-error {
			color: #dc3232;
		}

		/* Mobile Responsive */
		@media (max-width: 768px) {
			.tse-modal-content {
				width: 95%;
				max-height: 95vh;
			}

			.tse-modal-body {
				padding: 20px;
			}

			.tse-info-grid {
				grid-template-columns: 1fr;
			}

			.tse-quote-header {
				flex-direction: column;
				align-items: flex-start;
			}

			.tse-pricing-amount {
				font-size: 24px;
			}
		}
	</style>

	<!-- Modal JavaScript -->
	<script>
		jQuery(document).ready(function($) {
			var modal = $('#tse-quote-modal-overlay');
			var modalBody = $('#tse-modal-body');
			var modalTitle = $('#tse-modal-title');

			// Open modal when View Details clicked
			$('.tse-view-details').on('click', function() {
				var quoteId = $(this).data('quote-id');
				openQuoteModal(quoteId);
			});

			// Close modal on X button
			$('.tse-modal-close').on('click', function() {
				closeModal();
			});

			// Close modal on overlay click
			modal.on('click', function(e) {
				if ($(e.target).is('.tse-modal-overlay')) {
					closeModal();
				}
			});

			// Close modal on ESC key
			$(document).on('keydown', function(e) {
				if (e.key === 'Escape' && modal.is(':visible')) {
					closeModal();
				}
			});

			/**
			 * Open modal and load quote details via AJAX
			 */
			function openQuoteModal(quoteId) {
				// Show modal with loading state
				modal.fadeIn(200);
				modalBody.html('<div class="tse-loading">Loading quote details...</div>');
				modalTitle.text('Quote Details');

				// AJAX call to fetch quote details
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'tse_get_quote_details',
						nonce: '<?php echo wp_create_nonce('tse_quote_details_nonce'); ?>',
						quote_id: quoteId
					},
					success: function(response) {
						if (response.success) {
							displayQuoteDetails(response.data.quote);
						} else {
							modalBody.html('<div class="tse-loading" style="color: #c62828;">Error: ' + response.data.message + '</div>');
						}
					},
					error: function() {
						modalBody.html('<div class="tse-loading" style="color: #c62828;">Error loading quote details. Please try again.</div>');
					}
				});
			}

			/**
			 * Display quote details in modal
			 */
			function displayQuoteDetails(quote) {
				// Update modal title
				modalTitle.text('Quote #' + quote.quote_number);

				// Format status badge class
				var statusClass = 'tse-status-' + quote.status;
				var statusText = quote.status.charAt(0).toUpperCase() + quote.status.slice(1);

				// Format date
				var quoteDate = new Date(quote.created_at);
				var dateString = quoteDate.toLocaleDateString('en-US', {
					year: 'numeric',
					month: 'long',
					day: 'numeric',
					hour: 'numeric',
					minute: '2-digit'
				});

				// Format wall measurements
				var wall1 = parseFloat(quote.wall1_length).toFixed(1);
				var wall2 = parseFloat(quote.wall2_length).toFixed(1);
				var wall3 = parseFloat(quote.wall3_length).toFixed(1);
				var totalLinear = parseFloat(quote.total_linear_feet).toFixed(1);
				var totalSquare = formatNumber(parseFloat(quote.total_square_feet));

				// Parse notes
				var notes = [];
				if (quote.notes) {
					try {
						notes = JSON.parse(quote.notes);
						if (!Array.isArray(notes)) {
							notes = [];
						}
					} catch (e) {
						notes = [];
					}
				}

				// Build HTML
				var html = `
				<!-- Header Section -->
				<div class="tse-quote-header">
					<div class="tse-quote-number">#${quote.quote_number}</div>
					<div class="tse-quote-meta">
						<span class="tse-status-badge ${statusClass}">${statusText}</span>
						<span class="tse-quote-date">${dateString}</span>
					</div>
				</div>

				<!-- Customer Information -->
				<div class="tse-detail-section">
					<h3 class="tse-section-title">Customer Information</h3>
					<div class="tse-info-grid">
						<div>
							<div class="tse-info-item">
								<div class="tse-info-label">Name</div>
								<div class="tse-info-value">${escapeHtml(quote.full_name)}</div>
							</div>
							<div class="tse-info-item">
								<div class="tse-info-label">Email</div>
								<div class="tse-info-value"><a href="mailto:${escapeHtml(quote.email)}">${escapeHtml(quote.email)}</a></div>
							</div>
						</div>
						<div>
							<div class="tse-info-item">
								<div class="tse-info-label">Phone</div>
								<div class="tse-info-value"><a href="tel:${escapeHtml(quote.phone)}">${escapeHtml(quote.phone)}</a></div>
							</div>
							<div class="tse-info-item">
								<div class="tse-info-label">Address</div>
								<div class="tse-info-value">
									${escapeHtml(quote.address)}<br>
									${escapeHtml(quote.city)}, ${escapeHtml(quote.state)} ${escapeHtml(quote.zip)}
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Pricing -->
				<div class="tse-detail-section">
					<div class="tse-pricing-box">
						<div class="tse-pricing-label">Estimated Price Range</div>
						<div class="tse-pricing-amount">
							$${formatNumber(quote.price_min)} - $${formatNumber(quote.price_max)}
						</div>
						<div class="tse-pricing-details">
							${escapeHtml(quote.room_type_name)} • 
							${parseFloat(quote.total_linear_feet).toFixed(1)} Linear Feet • 
							${formatNumber(parseFloat(quote.total_square_feet))} Square Feet
						</div>
					</div>
				</div>

				<!-- Sunroom Details -->
				<div class="tse-detail-section">
					<h3 class="tse-section-title">Sunroom Details</h3>
					<div class="tse-info-grid">
						<div class="tse-info-item">
							<div class="tse-info-label">Room Type</div>
							<div class="tse-info-value">${escapeHtml(quote.room_type_name)}</div>
						</div>
					</div>

					<div class="tse-measurements-table">
						<div class="tse-measurement-row">
							<span class="tse-measurement-label">Wall 1:</span>
							<span class="tse-measurement-value">${wall1} ft</span>
						</div>
						<div class="tse-measurement-row">
							<span class="tse-measurement-label">Wall 2:</span>
							<span class="tse-measurement-value">${wall2} ft</span>
						</div>
						<div class="tse-measurement-row">
							<span class="tse-measurement-label">Wall 3:</span>
							<span class="tse-measurement-value">${wall3} ft</span>
						</div>
						<div class="tse-measurement-row">
							<span class="tse-measurement-label">Total:</span>
							<span class="tse-measurement-value">${totalLinear} linear feet (${totalSquare} sq ft)</span>
						</div>
					</div>
				</div>

				<!-- Project Details -->
				${quote.project_details || quote.timeline ? `
				<div class="tse-detail-section">
					<h3 class="tse-section-title">Project Details</h3>
					${quote.project_details ? `
					<div class="tse-info-item">
						<div class="tse-info-label">Description</div>
						<div class="tse-project-text">${escapeHtml(quote.project_details)}</div>
					</div>
					` : ''}
					${quote.timeline ? `
					<div class="tse-info-item" style="margin-top: 15px;">
						<div class="tse-info-label">Timeline</div>
						<div class="tse-info-value">${escapeHtml(quote.timeline)}</div>
					</div>
					` : ''}
				</div>
				` : ''}

				<!-- Internal Notes -->
				<div class="tse-detail-section tse-notes-section">
					<h3 class="tse-section-title">Internal Notes</h3>
					<div class="tse-notes-list" id="tse-notes-list">
						${renderNotes(notes)}
					</div>

					<!-- Add Note Form -->
					<div class="tse-add-note-form">
						<textarea id="tse-note-textarea" placeholder="Add a note about this quote..."></textarea>
						<button class="tse-add-note-btn" id="tse-add-note-btn" data-quote-id="${quote.id}">Add Note</button>
						<div id="tse-note-message"></div>
					</div>
				</div>

				<!-- Metadata -->
				<div class="tse-metadata">
					<div class="tse-metadata-grid">
						<div class="tse-info-item">
							<div class="tse-info-label">Submitted</div>
							<div class="tse-info-value">${dateString}</div>
						</div>
						<div class="tse-info-item">
							<div class="tse-info-label">IP Address</div>
							<div class="tse-info-value">${escapeHtml(quote.ip_address)}</div>
						</div>
						<div class="tse-info-item">
							<div class="tse-info-label">User Agent</div>
							<div class="tse-info-value" style="font-size: 11px; word-break: break-all;">${escapeHtml(quote.user_agent)}</div>
						</div>
					</div>
				</div>
			`;

				modalBody.html(html);

				// Attach event listener for Add Note button
				$('#tse-add-note-btn').on('click', function() {
					var quoteId = $(this).data('quote-id');
					var noteContent = $('#tse-note-textarea').val().trim();
					addNote(quoteId, noteContent);
				});
			}

			/**
			 * Render notes HTML
			 */
			function renderNotes(notes) {
				if (!notes || notes.length === 0) {
					return '<div class="tse-notes-empty">No notes yet. Add one below.</div>';
				}

				var html = '';
				notes.forEach(function(note) {
					var timeAgo = formatTimeAgo(note.timestamp);
					html += `
						<div class="tse-note-item">
							<div class="tse-note-content">${escapeHtml(note.content)}</div>
							<div class="tse-note-meta">
								<span class="tse-note-author">${escapeHtml(note.author)}</span>
								<span class="tse-note-time">${timeAgo}</span>
							</div>
						</div>
					`;
				});
				return html;
			}

			/**
			 * Add a new note via AJAX
			 */
			function addNote(quoteId, noteContent) {
				if (!noteContent) {
					showNoteMessage('Please enter a note', 'error');
					return;
				}

				// Disable button while saving
				var btn = $('#tse-add-note-btn');
				btn.prop('disabled', true).text('Saving...');

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'tse_add_quote_note',
						nonce: '<?php echo wp_create_nonce('tse_quote_notes_nonce'); ?>',
						quote_id: quoteId,
						note_content: noteContent
					},
					success: function(response) {
						if (response.success) {
							// Clear textarea
							$('#tse-note-textarea').val('');

							// Update notes display
							$('#tse-notes-list').html(renderNotes(response.data.notes));

							// Show success message
							showNoteMessage('Note added successfully!', 'success');
						} else {
							showNoteMessage(response.data.message || 'Failed to add note', 'error');
						}
					},
					error: function() {
						showNoteMessage('Error adding note. Please try again.', 'error');
					},
					complete: function() {
						// Re-enable button
						btn.prop('disabled', false).text('Add Note');
					}
				});
			}

			/**
			 * Show success/error message for notes
			 */
			function showNoteMessage(message, type) {
				var messageDiv = $('#tse-note-message');
				var className = type === 'success' ? 'tse-note-success' : 'tse-note-error';
				messageDiv.attr('class', className).text(message).show();

				// Hide after 3 seconds
				setTimeout(function() {
					messageDiv.fadeOut();
				}, 3000);
			}

			/**
			 * Format time ago ("2 hours ago" or "3 days ago")
			 */
			function formatTimeAgo(timestamp) {
				var now = new Date();
				var noteTime = new Date(timestamp.replace(/-/g, '/'));
				var diffMs = now - noteTime;
				var diffMins = Math.floor(diffMs / 60000);
				var diffHours = Math.floor(diffMs / 3600000);
				var diffDays = Math.floor(diffMs / 86400000);

				// Same day
				if (diffDays === 0) {
					if (diffMins < 1) return 'Just now';
					if (diffMins < 60) return diffMins + ' minute' + (diffMins === 1 ? '' : 's') + ' ago';
					return diffHours + ' hour' + (diffHours === 1 ? '' : 's') + ' ago';
				}

				// Different day
				if (diffDays === 1) return '1 day ago';
				return diffDays + ' days ago';
			}

			/**
			 * Close modal
			 */
			function closeModal() {
				modal.fadeOut(200);
			}

			/**
			 * Helper: Escape HTML
			 */
			function escapeHtml(text) {
				if (!text) return '';
				var map = {
					'&': '&amp;',
					'<': '&lt;',
					'>': '&gt;',
					'"': '&quot;',
					"'": '&#039;'
				};
				return text.toString().replace(/[&<>"']/g, function(m) {
					return map[m];
				});
			}

			/**
			 * Helper: Format number with commas
			 */
			function formatNumber(num) {
				return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
			}

			// ==============================================
			// ARCHIVE/RESTORE FUNCTIONALITY
			// ==============================================

			/**
			 * Handle Archive button click
			 */
			$('.tse-archive-quote').on('click', function() {
				var quoteId = $(this).data('quote-id');
				var button = $(this);
				var row = button.closest('tr');

				if (!confirm('Are you sure you want to archive this quote?')) {
					return;
				}

				// Disable button while processing
				button.prop('disabled', true).text('Archiving...');

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'tse_archive_quote',
						nonce: '<?php echo wp_create_nonce('tse_archive_nonce'); ?>',
						quote_id: quoteId,
						action_type: 'archive'
					},
					success: function(response) {
						if (response.success) {
							// Fade out and remove row
							row.fadeOut(300, function() {
								$(this).remove();
								// Reload page to update counts
								location.reload();
							});
						} else {
							alert('Error: ' + (response.data.message || 'Failed to archive quote'));
							button.prop('disabled', false).text('🗄️ Archive');
						}
					},
					error: function() {
						alert('Error archiving quote. Please try again.');
						button.prop('disabled', false).text('🗄️ Archive');
					}
				});
			});

			/**
			 * Handle Restore button click
			 */
			$('.tse-restore-quote').on('click', function() {
				var quoteId = $(this).data('quote-id');
				var button = $(this);
				var row = button.closest('tr');

				if (!confirm('Are you sure you want to restore this quote?')) {
					return;
				}

				// Disable button while processing
				button.prop('disabled', true).text('Restoring...');

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'tse_archive_quote',
						nonce: '<?php echo wp_create_nonce('tse_archive_nonce'); ?>',
						quote_id: quoteId,
						action_type: 'restore'
					},
					success: function(response) {
						if (response.success) {
							// Fade out and remove row
							row.fadeOut(300, function() {
								$(this).remove();
								// Reload page to update counts
								location.reload();
							});
						} else {
							alert('Error: ' + (response.data.message || 'Failed to restore quote'));
							button.prop('disabled', false).text('↻ Restore');
						}
					},
					error: function() {
						alert('Error restoring quote. Please try again.');
						button.prop('disabled', false).text('↻ Restore');
					}
				});
			});

			// ==============================================
			// QUICK EDIT STATUS FUNCTIONALITY
			// ==============================================

			/**
			 * Handle status dropdown change
			 */
			$('.tse-status-dropdown').on('change', function() {
				var dropdown = $(this);
				var quoteId = dropdown.data('quote-id');
				var newStatus = dropdown.val();
				var originalStatus = dropdown.find('option:selected').data('original') || dropdown.val();
				var indicator = dropdown.siblings('.tse-status-indicator');

				// Store original value in case we need to revert
				if (!dropdown.data('original-status')) {
					dropdown.data('original-status', originalStatus);
				}

				// Show saving indicator
				indicator.removeClass('tse-status-saved tse-status-error')
					.addClass('tse-status-saving')
					.text('💾 Saving...')
					.show();

				// Disable dropdown while saving
				dropdown.prop('disabled', true);

				// AJAX call to update status
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'tse_quick_edit_status',
						nonce: '<?php echo wp_create_nonce('tse_quick_edit_nonce'); ?>',
						quote_id: quoteId,
						status: newStatus
					},
					success: function(response) {
						if (response.success) {
							// Show success indicator
							indicator.removeClass('tse-status-saving')
								.addClass('tse-status-saved')
								.text('✓ Saved!');

							// Update stored original status
							dropdown.data('original-status', newStatus);

							// Hide indicator after 2 seconds
							setTimeout(function() {
								indicator.fadeOut();
							}, 2000);
						} else {
							// Show error indicator
							indicator.removeClass('tse-status-saving')
								.addClass('tse-status-error')
								.text('✗ Error');

							// Revert dropdown to original status
							dropdown.val(dropdown.data('original-status'));

							// Hide error after 3 seconds
							setTimeout(function() {
								indicator.fadeOut();
							}, 3000);
						}
					},
					error: function() {
						// Show error indicator
						indicator.removeClass('tse-status-saving')
							.addClass('tse-status-error')
							.text('✗ Error');

						// Revert dropdown to original status
						dropdown.val(dropdown.data('original-status'));

						// Hide error after 3 seconds
						setTimeout(function() {
							indicator.fadeOut();
						}, 3000);
					},
					complete: function() {
						// Re-enable dropdown
						dropdown.prop('disabled', false);
					}
				});
			});
		});
	</script>
<?php
}
