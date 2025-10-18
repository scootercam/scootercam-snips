<?php
/**
 * Plugin Name: Scootercam Weather Snips
 * Plugin URI: https://scootercam.com
 * Description: Displays random weather forecast snippets with admin management interface
 * Version: 1.0.0
 * Author: Scootercam
 * Author URI: https://scootercam.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Scootercam_Weather_Snips {
    
    private $base_path = '/home/scootercam/public_html';
    private $json_file;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->json_file = $this->base_path . '/wx/snips.json';
        
        add_shortcode('scootercam-snip', array($this, 'render_random_snip'));
        add_shortcode('scootercam-snips', array($this, 'render_random_snip'));
        
        // Admin interface
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_post_scootercam_save_snips', array($this, 'handle_save_snips'));
        add_action('admin_post_scootercam_delete_snip', array($this, 'handle_delete_snip'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'Weather Snips',
            'Weather Snips',
            'manage_options',
            'scootercam-snips',
            array($this, 'render_admin_page'),
            'dashicons-cloud',
            30
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_scootercam-snips') {
            return;
        }
        
        wp_enqueue_style('scootercam-snips-admin', false);
        wp_add_inline_style('scootercam-snips-admin', '
            .snips-container { max-width: 1200px; margin: 20px 0; }
            .snip-item { 
                background: #fff; 
                border: 1px solid #ddd; 
                padding: 15px; 
                margin-bottom: 10px;
                border-radius: 4px;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .snip-item:hover { background: #f9f9f9; }
            .snip-text { flex: 1; font-size: 14px; line-height: 1.5; }
            .snip-actions { display: flex; gap: 5px; }
            .new-snip-form { 
                background: #f0f0f1; 
                padding: 20px; 
                border-radius: 4px; 
                margin-bottom: 20px;
            }
            .new-snip-form textarea {
                width: 100%;
                min-height: 80px;
                padding: 10px;
                font-size: 14px;
            }
            .snip-count { 
                background: #2271b1; 
                color: white; 
                padding: 10px 15px; 
                border-radius: 4px;
                display: inline-block;
                margin-bottom: 20px;
            }
            .edit-snip-form { display: none; margin-top: 10px; }
            .edit-snip-form.active { display: block; }
            .edit-snip-form textarea { width: 100%; min-height: 60px; }
        ');
        
        wp_enqueue_script('scootercam-snips-admin', false, array('jquery'), '1.0', true);
        wp_add_inline_script('scootercam-snips-admin', '
            jQuery(document).ready(function($) {
                $(".edit-snip-btn").click(function(e) {
                    e.preventDefault();
                    $(this).closest(".snip-item").find(".edit-snip-form").addClass("active");
                    $(this).closest(".snip-item").find(".snip-text").hide();
                    $(this).hide();
                });
                
                $(".cancel-edit-btn").click(function(e) {
                    e.preventDefault();
                    $(this).closest(".edit-snip-form").removeClass("active");
                    $(this).closest(".snip-item").find(".snip-text").show();
                    $(this).closest(".snip-item").find(".edit-snip-btn").show();
                });
                
                $(".delete-snip-btn").click(function(e) {
                    if (!confirm("Are you sure you want to delete this snip?")) {
                        e.preventDefault();
                    }
                });
            });
        ');
    }
    
    /**
     * Read snips from JSON file
     */
    private function getSnips() {
        if (!file_exists($this->json_file)) {
            return array();
        }
        
        $json_data = @file_get_contents($this->json_file);
        if ($json_data === false) {
            return array();
        }
        
        $data = json_decode($json_data, true);
        
        if (!isset($data['forecasts']) || !is_array($data['forecasts'])) {
            return array();
        }
        
        return $data['forecasts'];
    }
    
    /**
     * Save snips to JSON file
     */
    private function saveSnips($snips) {
        $data = array('forecasts' => array_values($snips));
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        return @file_put_contents($this->json_file, $json) !== false;
    }
    
    /**
     * Handle save snips (add or update)
     */
    public function handle_save_snips() {
        check_admin_referer('scootercam_save_snips');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $snips = $this->getSnips();
        
        // Add new snip
        if (isset($_POST['new_snip']) && !empty(trim($_POST['new_snip']))) {
            $new_snip = sanitize_textarea_field($_POST['new_snip']);
            $snips[] = $new_snip;
        }
        
        // Update existing snip
        if (isset($_POST['edit_snip']) && isset($_POST['snip_index'])) {
            $index = intval($_POST['snip_index']);
            $updated_snip = sanitize_textarea_field($_POST['edit_snip']);
            
            if (isset($snips[$index])) {
                $snips[$index] = $updated_snip;
            }
        }
        
        if ($this->saveSnips($snips)) {
            wp_redirect(add_query_arg('message', 'saved', admin_url('admin.php?page=scootercam-snips')));
        } else {
            wp_redirect(add_query_arg('message', 'error', admin_url('admin.php?page=scootercam-snips')));
        }
        exit;
    }
    
    /**
     * Handle delete snip
     */
    public function handle_delete_snip() {
        check_admin_referer('scootercam_delete_snip');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        if (!isset($_GET['index'])) {
            wp_die('Invalid request');
        }
        
        $index = intval($_GET['index']);
        $snips = $this->getSnips();
        
        if (isset($snips[$index])) {
            unset($snips[$index]);
            
            if ($this->saveSnips($snips)) {
                wp_redirect(add_query_arg('message', 'deleted', admin_url('admin.php?page=scootercam-snips')));
            } else {
                wp_redirect(add_query_arg('message', 'error', admin_url('admin.php?page=scootercam-snips')));
            }
        }
        
        exit;
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        $snips = $this->getSnips();
        $message = isset($_GET['message']) ? $_GET['message'] : '';
        
        ?>
        <div class="wrap">
            <h1>Weather Snips Manager</h1>
            
            <?php if ($message === 'saved'): ?>
                <div class="notice notice-success is-dismissible">
                    <p>Snip saved successfully!</p>
                </div>
            <?php elseif ($message === 'deleted'): ?>
                <div class="notice notice-success is-dismissible">
                    <p>Snip deleted successfully!</p>
                </div>
            <?php elseif ($message === 'error'): ?>
                <div class="notice notice-error is-dismissible">
                    <p>Error saving snip. Please check file permissions.</p>
                </div>
            <?php endif; ?>
            
            <div class="snips-container">
                <!-- Add New Snip Form -->
                <div class="new-snip-form">
                    <h2>Add New Snip</h2>
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                        <?php wp_nonce_field('scootercam_save_snips'); ?>
                        <input type="hidden" name="action" value="scootercam_save_snips">
                        <p>
                            <textarea name="new_snip" placeholder="Enter your weather snip here..." required></textarea>
                        </p>
                        <p>
                            <button type="submit" class="button button-primary">Add Snip</button>
                        </p>
                    </form>
                </div>
                
                <!-- Snip Count -->
                <div class="snip-count">
                    Total Snips: <strong><?php echo count($snips); ?></strong>
                </div>
                
                <!-- List Existing Snips -->
                <h2>Existing Snips</h2>
                
                <?php if (empty($snips)): ?>
                    <p>No snips found. Add your first snip above!</p>
                <?php else: ?>
                    <?php foreach ($snips as $index => $snip): ?>
                        <div class="snip-item">
                            <div class="snip-text"><?php echo esc_html($snip); ?></div>
                            
                            <!-- Edit Form (hidden by default) -->
                            <div class="edit-snip-form">
                                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                                    <?php wp_nonce_field('scootercam_save_snips'); ?>
                                    <input type="hidden" name="action" value="scootercam_save_snips">
                                    <input type="hidden" name="snip_index" value="<?php echo $index; ?>">
                                    <textarea name="edit_snip" required><?php echo esc_textarea($snip); ?></textarea>
                                    <div style="margin-top: 10px;">
                                        <button type="submit" class="button button-primary">Save</button>
                                        <button type="button" class="button cancel-edit-btn">Cancel</button>
                                    </div>
                                </form>
                            </div>
                            
                            <div class="snip-actions">
                                <button class="button edit-snip-btn">Edit</button>
                                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=scootercam_delete_snip&index=' . $index), 'scootercam_delete_snip'); ?>" 
                                   class="button delete-snip-btn">Delete</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div style="margin-top: 30px; padding: 15px; background: #f0f0f1; border-radius: 4px;">
                <h3>Usage</h3>
                <p>Use the shortcode <code>[scootercam-snip]</code> or <code>[scootercam-snips]</code> anywhere on your site to display a random snip.</p>
                <p><strong>Examples:</strong></p>
                <ul>
                    <li><code>[scootercam-snip]</code> - Basic usage</li>
                    <li><code>[scootercam-snip class="forecast-box"]</code> - With custom CSS class</li>
                    <li><code>[scootercam-snip wrapper="div"]</code> - Use div instead of p tag</li>
                    <li><code>[scootercam-snip prefix="Forecast: "]</code> - Add text before snip</li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get random forecast snippet
     */
    private function getRandomSnip() {
        $snips = $this->getSnips();
        
        if (empty($snips)) {
            return null;
        }
        
        return $snips[array_rand($snips)];
    }
    
    /**
     * Shortcode callback function
     */
    public function render_random_snip($atts) {
        $atts = shortcode_atts(array(
            'wrapper' => 'p',
            'class' => '',
            'style' => '',
            'prefix' => '',
            'suffix' => ''
        ), $atts);
        
        $snip = $this->getRandomSnip();
        
        if ($snip === null) {
            return '<!-- No weather snips available -->';
        }
        
        // Sanitize attributes
        $wrapper = sanitize_text_field($atts['wrapper']);
        $class = sanitize_text_field($atts['class']);
        $style = sanitize_text_field($atts['style']);
        $prefix = wp_kses_post($atts['prefix']);
        $suffix = wp_kses_post($atts['suffix']);
        
        // Build HTML
        $class_attr = !empty($class) ? ' class="' . esc_attr($class) . '"' : '';
        $style_attr = !empty($style) ? ' style="' . esc_attr($style) . '"' : '';
        
        $html = '<' . $wrapper . $class_attr . $style_attr . '>';
        $html .= $prefix;
        $html .= esc_html($snip);
        $html .= $suffix;
        $html .= '</' . $wrapper . '>';
        
        return $html;
    }
}

// Initialize the plugin
new Scootercam_Weather_Snips();