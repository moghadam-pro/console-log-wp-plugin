<?php
/**
 * Plugin Name: MPRO Console Log
 * Plugin URI:  https://moghadam.pro/mpro-plugins/
 * Description: Animated, configurable console-style log stream with live preview and shortcode support.
 * Version:     1.1.1
 * Author:      Sayid Moghadam
 * Text Domain: mpro-console-log
 * Requires at least: 6.2
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class MPRO_Console_Log {
    const VERSION     = '1.1.1';
    const PROMO_URL   = 'https://moghadam.pro/mpro-plugins/';
    const OPTION_NAME = 'mpro_console_log_settings';
    const PAGE_SLUG   = 'mpro-console-log';

    /** @var MPRO_Console_Log|null */
    private static $instance = null;

    /** @var string */
    private $admin_hook = '';

    /** @var bool */
    private $frontend_assets_registered = false;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ), 99 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'admin_post_mpro_console_log_save', array( $this, 'save_settings' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
        add_shortcode( 'mpro_console', array( $this, 'render_shortcode' ) );
        add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
        add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
    }

    public static function defaults() {
        return array(
            'logs' => implode( "\n", array(
                'INIT|Booting portfolio interface…',
                'SYSTEM|Loading design tokens and components…',
                'DATA|Connecting to selected product archive…',
                'UX|Mapping journeys, states and edge cases…',
                'RENDER|Composing the hero experience…',
                'SUCCESS|Interface ready.',
            ) ),
            'base_interval'  => 760,
            'variation'      => 52,
            'scroll_duration'=> 520,
            'rhythm'         => 'organic',
            'easing'         => 'ease-in-out-cubic',
            'max_lines'      => 25,
            'width'          => '100%',
            'height'         => '220px',
            'font_size'      => '11px',
            'line_height'    => 1.45,
            'text_color_slug'=> 'inherit',
            'text_opacity'   => 0.55,
            'show_timestamp' => 1,
            'show_category'  => 1,
            'mask_fade'      => 1,
            'loop'           => 1,
        );
    }

    public function register_admin_menu() {
        $parent_slug = $this->find_mpro_parent_slug();

        if ( ! $parent_slug ) {
            $parent_slug = 'mpro';
            add_menu_page(
                'MPRO',
                'MPRO',
                'manage_options',
                $parent_slug,
                array( $this, 'render_mpro_landing' ),
                'dashicons-layout',
                58
            );
        }

        $this->admin_hook = add_submenu_page(
            $parent_slug,
            __( 'Console Log', 'mpro-console-log' ),
            __( 'Console Log', 'mpro-console-log' ),
            'manage_options',
            self::PAGE_SLUG,
            array( $this, 'render_admin_page' )
        );
    }

    private function find_mpro_parent_slug() {
        global $menu;

        if ( ! is_array( $menu ) ) {
            return '';
        }

        foreach ( $menu as $item ) {
            $label = isset( $item[0] ) ? trim( wp_strip_all_tags( $item[0] ) ) : '';
            $slug  = isset( $item[2] ) ? (string) $item[2] : '';

            if ( 'MPRO' === strtoupper( $label ) || 'mpro' === strtolower( $slug ) ) {
                return $slug;
            }
        }

        foreach ( $menu as $item ) {
            $label = isset( $item[0] ) ? strtolower( trim( wp_strip_all_tags( $item[0] ) ) ) : '';
            $slug  = isset( $item[2] ) ? strtolower( (string) $item[2] ) : '';

            if ( false !== strpos( $label, 'mpro' ) || false !== strpos( $slug, 'mpro' ) ) {
                return isset( $item[2] ) ? (string) $item[2] : '';
            }
        }

        return '';
    }

    public function render_mpro_landing() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        echo '<div class="wrap"><h1>MPRO</h1><p>' . esc_html__( 'Custom MPRO tools are available from this menu.', 'mpro-console-log' ) . '</p></div>';
    }

    public function register_frontend_assets() {
        if ( $this->frontend_assets_registered ) {
            return;
        }

        wp_register_style(
            'mpro-console-log',
            plugin_dir_url( __FILE__ ) . 'assets/css/frontend.css',
            array(),
            self::VERSION
        );

        wp_register_script(
            'mpro-console-log',
            plugin_dir_url( __FILE__ ) . 'assets/js/frontend.js',
            array(),
            self::VERSION,
            true
        );

        $this->frontend_assets_registered = true;
    }

    public function enqueue_public_assets() {
        $this->register_frontend_assets();
        wp_enqueue_style( 'mpro-console-log' );
        wp_enqueue_script( 'mpro-console-log' );
    }

    public function enqueue_admin_assets( $hook ) {
        if ( $hook !== $this->admin_hook && ( ! isset( $_GET['page'] ) || self::PAGE_SLUG !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) ) {
            return;
        }

        $this->register_frontend_assets();
        wp_enqueue_style( 'mpro-console-log' );
        wp_enqueue_script( 'mpro-console-log' );

        wp_enqueue_style(
            'mpro-console-log-admin',
            plugin_dir_url( __FILE__ ) . 'assets/css/admin.css',
            array( 'mpro-console-log' ),
            self::VERSION
        );
        wp_enqueue_script(
            'mpro-console-log-admin',
            plugin_dir_url( __FILE__ ) . 'assets/js/admin.js',
            array( 'mpro-console-log' ),
            self::VERSION,
            true
        );
    }

    public function save_settings() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to change these settings.', 'mpro-console-log' ) );
        }

        check_admin_referer( 'mpro_console_log_save_settings' );

        $raw      = isset( $_POST['mpro_console'] ) && is_array( $_POST['mpro_console'] ) ? wp_unslash( $_POST['mpro_console'] ) : array();
        $settings = $this->sanitize_settings( $raw );
        update_option( self::OPTION_NAME, $settings, false );

        $redirect = add_query_arg(
            array(
                'page'    => self::PAGE_SLUG,
                'updated' => '1',
            ),
            admin_url( 'admin.php' )
        );
        wp_safe_redirect( $redirect );
        exit;
    }

    private function sanitize_settings( $raw ) {
        $defaults = self::defaults();
        $easings  = array_keys( $this->easing_options() );
        $rhythms  = array_keys( $this->rhythm_options() );

        $settings = array();
        $settings['logs']            = isset( $raw['logs'] ) ? sanitize_textarea_field( $raw['logs'] ) : $defaults['logs'];
        $settings['base_interval']   = $this->clamp_int( $raw['base_interval'] ?? $defaults['base_interval'], 80, 10000 );
        $settings['variation']       = $this->clamp_int( $raw['variation'] ?? $defaults['variation'], 0, 95 );
        $settings['scroll_duration'] = $this->clamp_int( $raw['scroll_duration'] ?? $defaults['scroll_duration'], 0, 5000 );
        $settings['rhythm']          = in_array( $raw['rhythm'] ?? '', $rhythms, true ) ? $raw['rhythm'] : $defaults['rhythm'];
        $settings['easing']          = in_array( $raw['easing'] ?? '', $easings, true ) ? $raw['easing'] : $defaults['easing'];
        $settings['max_lines']       = $this->clamp_int( $raw['max_lines'] ?? $defaults['max_lines'], 2, 200 );
        $settings['width']           = $this->sanitize_css_size( $raw['width'] ?? $defaults['width'], $defaults['width'], true );
        $settings['height']          = $this->sanitize_css_size( $raw['height'] ?? $defaults['height'], $defaults['height'], false );
        $settings['font_size']       = $this->sanitize_css_size( $raw['font_size'] ?? $defaults['font_size'], $defaults['font_size'], false );
        $settings['line_height']     = $this->clamp_float( $raw['line_height'] ?? $defaults['line_height'], 1, 3 );
        $settings['text_color_slug'] = $this->sanitize_theme_color_slug( $raw['text_color_slug'] ?? $defaults['text_color_slug'] );
        $settings['text_opacity']    = $this->clamp_float( $raw['text_opacity'] ?? $defaults['text_opacity'], 0.05, 1 );
        $settings['show_timestamp']  = empty( $raw['show_timestamp'] ) ? 0 : 1;
        $settings['show_category']   = empty( $raw['show_category'] ) ? 0 : 1;
        $settings['mask_fade']       = empty( $raw['mask_fade'] ) ? 0 : 1;
        $settings['loop']            = empty( $raw['loop'] ) ? 0 : 1;

        return $settings;
    }

    private function clamp_int( $value, $min, $max ) {
        return max( $min, min( $max, absint( $value ) ) );
    }

    private function clamp_float( $value, $min, $max ) {
        $number = is_numeric( $value ) ? (float) $value : (float) $min;
        return max( $min, min( $max, $number ) );
    }

    private function sanitize_css_size( $value, $fallback, $allow_auto = false ) {
        $value = trim( (string) $value );
        if ( $allow_auto && 'auto' === strtolower( $value ) ) {
            return 'auto';
        }
        if ( preg_match( '/^\d+(?:\.\d+)?(?:px|%|rem|em|vw|vh|vmin|vmax)$/i', $value ) ) {
            return $value;
        }
        if ( preg_match( '/^\d+(?:\.\d+)?$/', $value ) ) {
            return $value . 'px';
        }
        return $fallback;
    }

    private function easing_options() {
        return array(
            'ease-in-out'       => __( 'Classic ease-in-out', 'mpro-console-log' ),
            'ease-in-out-sine'  => __( 'Ease In Out · Sine', 'mpro-console-log' ),
            'ease-in-out-quad'  => __( 'Ease In Out · Quad', 'mpro-console-log' ),
            'ease-in-out-cubic' => __( 'Ease In Out · Cubic', 'mpro-console-log' ),
            'ease-in-out-quart' => __( 'Ease In Out · Quart', 'mpro-console-log' ),
            'ease-in-out-quint' => __( 'Ease In Out · Quint', 'mpro-console-log' ),
            'ease-in-out-back'  => __( 'Ease In Out · Back', 'mpro-console-log' ),
            'random'            => __( 'Random easing per line', 'mpro-console-log' ),
        );
    }

    private function rhythm_options() {
        return array(
            'organic'   => __( 'Organic — balanced variation', 'mpro-console-log' ),
            'breathing' => __( 'Breathing — smooth waves', 'mpro-console-log' ),
            'bursts'    => __( 'Bursts — quick groups and pauses', 'mpro-console-log' ),
            'random'    => __( 'Random — fully irregular', 'mpro-console-log' ),
        );
    }

    private function get_settings() {
        $saved = get_option( self::OPTION_NAME, array() );
        $saved = is_array( $saved ) ? $saved : array();

        if ( empty( $saved['text_color_slug'] ) ) {
            $saved['text_color_slug'] = 'inherit';
            if ( ! empty( $saved['text_color'] ) ) {
                foreach ( $this->get_theme_palette() as $slug => $color ) {
                    if ( 0 === strcasecmp( (string) $saved['text_color'], (string) $color['color'] ) ) {
                        $saved['text_color_slug'] = $slug;
                        break;
                    }
                }
            }
        }

        $saved['text_color_slug'] = $this->sanitize_theme_color_slug( $saved['text_color_slug'] );
        return wp_parse_args( $saved, self::defaults() );
    }

    private function parse_lines( $text ) {
        $lines = preg_split( '/\r\n|\r|\n/', (string) $text );
        $lines = array_values( array_filter( array_map( 'trim', $lines ), static function( $line ) {
            return '' !== $line;
        } ) );
        return $lines;
    }

    private function bool_value( $value, $fallback ) {
        if ( null === $value || '' === $value ) {
            return (bool) $fallback;
        }
        return ! in_array( strtolower( (string) $value ), array( '0', 'false', 'off', 'no' ), true );
    }

    public function render_shortcode( $atts = array(), $content = null ) {
        $settings = $this->get_settings();
        $atts = shortcode_atts(
            array(
                'width'           => $settings['width'],
                'height'          => $settings['height'],
                'font_size'       => $settings['font_size'],
                'line_height'     => $settings['line_height'],
                'color'           => $settings['text_color_slug'],
                'max_lines'       => $settings['max_lines'],
                'interval'        => $settings['base_interval'],
                'variation'       => $settings['variation'],
                'scroll_duration' => $settings['scroll_duration'],
                'rhythm'          => $settings['rhythm'],
                'easing'          => $settings['easing'],
                'timestamp'       => $settings['show_timestamp'],
                'category'        => $settings['show_category'],
                'mask'            => $settings['mask_fade'],
                'loop'            => $settings['loop'],
                'class'           => '',
            ),
            $atts,
            'mpro_console'
        );

        $runtime = $settings;
        $runtime['width']           = $this->sanitize_css_size( $atts['width'], $settings['width'], true );
        $runtime['height']          = $this->sanitize_css_size( $atts['height'], $settings['height'], false );
        $runtime['font_size']       = $this->sanitize_css_size( $atts['font_size'], $settings['font_size'], false );
        $runtime['line_height']     = $this->clamp_float( $atts['line_height'], 1, 3 );
        $runtime['text_color_slug'] = $this->sanitize_theme_color_slug( $atts['color'] );
        $runtime['max_lines']       = $this->clamp_int( $atts['max_lines'], 2, 200 );
        $runtime['base_interval']   = $this->clamp_int( $atts['interval'], 80, 10000 );
        $runtime['variation']       = $this->clamp_int( $atts['variation'], 0, 95 );
        $runtime['scroll_duration'] = $this->clamp_int( $atts['scroll_duration'], 0, 5000 );
        $runtime['rhythm']          = array_key_exists( $atts['rhythm'], $this->rhythm_options() ) ? $atts['rhythm'] : $settings['rhythm'];
        $runtime['easing']          = array_key_exists( $atts['easing'], $this->easing_options() ) ? $atts['easing'] : $settings['easing'];
        $runtime['show_timestamp']  = $this->bool_value( $atts['timestamp'], $settings['show_timestamp'] ) ? 1 : 0;
        $runtime['show_category']   = $this->bool_value( $atts['category'], $settings['show_category'] ) ? 1 : 0;
        $runtime['mask_fade']       = $this->bool_value( $atts['mask'], $settings['mask_fade'] ) ? 1 : 0;
        $runtime['loop']            = $this->bool_value( $atts['loop'], $settings['loop'] ) ? 1 : 0;

        $custom_lines = null !== $content ? $this->parse_lines( sanitize_textarea_field( $content ) ) : array();
        $lines        = ! empty( $custom_lines ) ? $custom_lines : $this->parse_lines( $settings['logs'] );

        $this->register_frontend_assets();
        wp_enqueue_style( 'mpro-console-log' );
        wp_enqueue_script( 'mpro-console-log' );

        return $this->render_console( $runtime, $lines, $atts['class'] );
    }

    private function render_console( $settings, $lines, $extra_class = '', $is_preview = false ) {
        $id         = wp_unique_id( 'mpro-console-' );
        $color_css  = $this->theme_color_css( $settings['text_color_slug'], $is_preview );
        $class_list = array( 'mpro-console-log' );

        if ( ! empty( $settings['mask_fade'] ) ) {
            $class_list[] = 'mpro-console-log--masked';
        }
        if ( $is_preview ) {
            $class_list[] = 'mpro-console-log--preview';
        }
        if ( $extra_class ) {
            foreach ( preg_split( '/\s+/', (string) $extra_class ) as $class_name ) {
                $class_name = sanitize_html_class( $class_name );
                if ( $class_name ) {
                    $class_list[] = $class_name;
                }
            }
        }

        $style = implode( ';', array(
            '--mpro-console-width:' . $settings['width'],
            '--mpro-console-height:' . $settings['height'],
            '--mpro-console-font-size:' . $settings['font_size'],
            '--mpro-console-line-height:' . (float) $settings['line_height'],
            '--mpro-console-color:' . $color_css,
            '--mpro-console-opacity:' . (float) $settings['text_opacity'],
        ) );

        $data = array(
            'baseInterval'  => (int) $settings['base_interval'],
            'variation'     => (int) $settings['variation'],
            'scrollDuration'=> (int) $settings['scroll_duration'],
            'rhythm'        => (string) $settings['rhythm'],
            'easing'        => (string) $settings['easing'],
            'maxLines'      => (int) $settings['max_lines'],
            'showTimestamp' => ! empty( $settings['show_timestamp'] ),
            'showCategory'  => ! empty( $settings['show_category'] ),
            'loop'          => ! empty( $settings['loop'] ),
        );

        ob_start();
        ?>
        <div
            id="<?php echo esc_attr( $id ); ?>"
            class="<?php echo esc_attr( implode( ' ', array_unique( $class_list ) ) ); ?>"
            style="<?php echo esc_attr( $style ); ?>"
            aria-live="polite"
            aria-label="<?php echo esc_attr__( 'Animated console log', 'mpro-console-log' ); ?>"
        >
            <div class="mpro-console-log__track"></div>
            <script type="application/json" class="mpro-console-log__lines"><?php echo wp_json_encode( array_values( $lines ), JSON_UNESCAPED_UNICODE ); ?></script>
            <script type="application/json" class="mpro-console-log__settings"><?php echo wp_json_encode( $data, JSON_UNESCAPED_UNICODE ); ?></script>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_promo_url() {
        return esc_url( apply_filters( 'mpro_console_log_promo_url', self::PROMO_URL ) );
    }

    public function plugin_row_meta( $links, $file ) {
        if ( plugin_basename( __FILE__ ) !== $file ) {
            return $links;
        }

        $links[] = esc_html__( 'Version', 'mpro-console-log' ) . ' ' . esc_html( self::VERSION );
        $links[] = '<a href="' . esc_url( $this->get_promo_url() ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'MPRO Plugins', 'mpro-console-log' ) . '</a>';
        return $links;
    }

    public function plugin_action_links( $links ) {
        $settings_url = add_query_arg( 'page', self::PAGE_SLUG, admin_url( 'admin.php' ) );
        array_unshift( $links, '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'mpro-console-log' ) . '</a>' );
        $links[] = '<a href="' . esc_url( $this->get_promo_url() ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'MPRO Plugins', 'mpro-console-log' ) . '</a>';
        return $links;
    }

    private function get_theme_palette() {
        $palette = array();

        if ( function_exists( 'wp_get_global_settings' ) ) {
            $global_settings = wp_get_global_settings();
            if ( isset( $global_settings['color']['palette']['theme'] ) && is_array( $global_settings['color']['palette']['theme'] ) ) {
                $palette = $global_settings['color']['palette']['theme'];
            }
        }

        $normalized = array();
        foreach ( $palette as $color ) {
            if ( empty( $color['slug'] ) || empty( $color['color'] ) ) {
                continue;
            }
            $slug = sanitize_key( $color['slug'] );
            if ( ! $slug ) {
                continue;
            }
            $normalized[ $slug ] = array(
                'slug'  => $slug,
                'name'  => isset( $color['name'] ) ? wp_strip_all_tags( $color['name'] ) : $slug,
                'color' => sanitize_text_field( $color['color'] ),
            );
        }

        return $normalized;
    }

    private function sanitize_theme_color_slug( $slug ) {
        $slug = sanitize_key( (string) $slug );
        if ( ! $slug || 'inherit' === $slug ) {
            return 'inherit';
        }

        $palette = $this->get_theme_palette();
        return isset( $palette[ $slug ] ) ? $slug : 'inherit';
    }

    private function theme_color_css( $slug, $is_preview = false ) {
        $slug = $this->sanitize_theme_color_slug( $slug );
        if ( 'inherit' === $slug ) {
            return 'currentColor';
        }

        $palette  = $this->get_theme_palette();
        $fallback = isset( $palette[ $slug ]['color'] ) ? $palette[ $slug ]['color'] : 'currentColor';

        if ( $is_preview ) {
            return $fallback;
        }

        return 'var(--wp--preset--color--' . $slug . ', ' . $fallback . ')';
    }

    private function render_plugin_meta() {
        ?>
        <span class="mpro-console-plugin-meta">
            <?php echo esc_html( sprintf( __( 'Version %s', 'mpro-console-log' ), self::VERSION ) ); ?>
            <span aria-hidden="true">·</span>
            <a href="<?php echo esc_url( $this->get_promo_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'MPRO Plugins', 'mpro-console-log' ); ?></a>
        </span>
        <?php
    }

    public function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $settings = $this->get_settings();
        $lines    = $this->parse_lines( $settings['logs'] );
        ?>
        <div class="wrap mpro-console-admin">
            <div class="mpro-console-admin__header">
                <div>
                    <h1 class="wp-heading-inline"><?php esc_html_e( 'MPRO Console Log', 'mpro-console-log' ); ?></h1>
                    <p class="description"><?php esc_html_e( 'Build an organic console stream and place it anywhere with a shortcode.', 'mpro-console-log' ); ?></p>
                    <?php $this->render_plugin_meta(); ?>
                </div>
                <code>[mpro_console]</code>
            </div>
            <hr class="wp-header-end">

            <?php if ( isset( $_GET['updated'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Console settings saved.', 'mpro-console-log' ); ?></p></div>
            <?php endif; ?>

            <form class="mpro-console-admin__form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
                <input type="hidden" name="action" value="mpro_console_log_save">
                <?php wp_nonce_field( 'mpro_console_log_save_settings' ); ?>

                <div class="mpro-console-admin__layout">
                    <div class="mpro-console-admin__controls">
                        <section class="postbox mpro-console-card">
                            <div class="mpro-console-card__heading">
                                <div>
                                    <h2><?php esc_html_e( 'Log content', 'mpro-console-log' ); ?></h2>
                                    <p><?php esc_html_e( 'One log per line. Use CATEGORY|Message to add a category.', 'mpro-console-log' ); ?></p>
                                </div>
                                <span class="mpro-console-card__count"><strong data-mpro-line-count><?php echo esc_html( count( $lines ) ); ?></strong> lines</span>
                            </div>
                            <textarea id="mpro-console-logs" class="large-text code" name="mpro_console[logs]" rows="12" spellcheck="false"><?php echo esc_textarea( $settings['logs'] ); ?></textarea>
                        </section>

                        <section class="postbox mpro-console-card">
                            <div class="mpro-console-card__heading">
                                <div>
                                    <h2><?php esc_html_e( 'Motion & rhythm', 'mpro-console-log' ); ?></h2>
                                    <p><?php esc_html_e( 'Control how often lines appear and how the stack moves upward.', 'mpro-console-log' ); ?></p>
                                </div>
                            </div>

                            <div class="mpro-console-fields mpro-console-fields--two">
                                <?php $this->number_field( 'base_interval', __( 'Base add interval', 'mpro-console-log' ), $settings['base_interval'], 80, 10000, 10, 'ms' ); ?>
                                <?php $this->number_field( 'variation', __( 'Timing variation', 'mpro-console-log' ), $settings['variation'], 0, 95, 1, '%' ); ?>
                                <?php $this->number_field( 'scroll_duration', __( 'Upward motion duration', 'mpro-console-log' ), $settings['scroll_duration'], 0, 5000, 10, 'ms' ); ?>
                                <?php $this->number_field( 'max_lines', __( 'Maximum visible lines', 'mpro-console-log' ), $settings['max_lines'], 2, 200, 1, '' ); ?>

                                <label class="mpro-console-field">
                                    <span><?php esc_html_e( 'Rhythm mode', 'mpro-console-log' ); ?></span>
                                    <select name="mpro_console[rhythm]" data-mpro-setting="rhythm">
                                        <?php foreach ( $this->rhythm_options() as $value => $label ) : ?>
                                            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['rhythm'], $value ); ?>><?php echo esc_html( $label ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>

                                <label class="mpro-console-field">
                                    <span><?php esc_html_e( 'Movement easing', 'mpro-console-log' ); ?></span>
                                    <select name="mpro_console[easing]" data-mpro-setting="easing">
                                        <?php foreach ( $this->easing_options() as $value => $label ) : ?>
                                            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['easing'], $value ); ?>><?php echo esc_html( $label ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            </div>
                        </section>

                        <section class="postbox mpro-console-card">
                            <div class="mpro-console-card__heading">
                                <div>
                                    <h2><?php esc_html_e( 'Appearance', 'mpro-console-log' ); ?></h2>
                                    <p><?php esc_html_e( 'These defaults can be overridden directly in the shortcode.', 'mpro-console-log' ); ?></p>
                                </div>
                            </div>

                            <div class="mpro-console-fields mpro-console-fields--two">
                                <?php $this->text_field( 'width', __( 'Default width', 'mpro-console-log' ), $settings['width'], '100%, 420px, 40vw' ); ?>
                                <?php $this->text_field( 'height', __( 'Default height', 'mpro-console-log' ), $settings['height'], '220px' ); ?>
                                <?php $this->text_field( 'font_size', __( 'Font size', 'mpro-console-log' ), $settings['font_size'], '11px' ); ?>
                                <?php $this->number_field( 'line_height', __( 'Line height', 'mpro-console-log' ), $settings['line_height'], 1, 3, 0.05, '' ); ?>

                                <label class="mpro-console-field">
                                    <span><?php esc_html_e( 'Theme text color', 'mpro-console-log' ); ?></span>
                                    <select name="mpro_console[text_color_slug]" data-mpro-setting="text_color_slug">
                                        <option value="inherit" data-color="currentColor" <?php selected( $settings['text_color_slug'], 'inherit' ); ?>><?php esc_html_e( 'Inherit from placement (recommended)', 'mpro-console-log' ); ?></option>
                                        <?php foreach ( $this->get_theme_palette() as $slug => $color ) : ?>
                                            <option value="<?php echo esc_attr( $slug ); ?>" data-color="<?php echo esc_attr( $color['color'] ); ?>" <?php selected( $settings['text_color_slug'], $slug ); ?>><?php echo esc_html( $color['name'] ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="description"><?php esc_html_e( 'Colors are read directly from the active theme palette.', 'mpro-console-log' ); ?></small>
                                </label>
                                <?php $this->number_field( 'text_opacity', __( 'Text opacity', 'mpro-console-log' ), $settings['text_opacity'], 0.05, 1, 0.05, '' ); ?>
                            </div>

                            <div class="mpro-console-toggles">
                                <?php $this->checkbox_field( 'show_timestamp', __( 'Show timestamp', 'mpro-console-log' ), $settings['show_timestamp'] ); ?>
                                <?php $this->checkbox_field( 'show_category', __( 'Show category', 'mpro-console-log' ), $settings['show_category'] ); ?>
                                <?php $this->checkbox_field( 'mask_fade', __( 'Fade older lines', 'mpro-console-log' ), $settings['mask_fade'] ); ?>
                                <?php $this->checkbox_field( 'loop', __( 'Loop continuously', 'mpro-console-log' ), $settings['loop'] ); ?>
                            </div>
                        </section>

                        <section class="postbox mpro-console-card mpro-console-shortcode-help">
                            <div class="mpro-console-card__heading">
                                <div>
                                    <h2><?php esc_html_e( 'Shortcode examples', 'mpro-console-log' ); ?></h2>
                                    <p><?php esc_html_e( 'Use Elementor’s Shortcode widget or any WordPress content area.', 'mpro-console-log' ); ?></p>
                                </div>
                            </div>
                            <button type="button" class="button mpro-console-copy" data-copy="[mpro_console]"><code>[mpro_console]</code><span><?php esc_html_e( 'Copy', 'mpro-console-log' ); ?></span></button>
                            <button type="button" class="button mpro-console-copy" data-copy='[mpro_console width="520px" height="280px" font_size="12px"]'><code>[mpro_console width="520px" height="280px" font_size="12px"]</code><span><?php esc_html_e( 'Copy', 'mpro-console-log' ); ?></span></button>
                            <button type="button" class="button mpro-console-copy" data-copy='[mpro_console interval="420" variation="70" easing="random" rhythm="bursts"]'><code>[mpro_console interval="420" variation="70" easing="random" rhythm="bursts"]</code><span><?php esc_html_e( 'Copy', 'mpro-console-log' ); ?></span></button>
                        </section>

                        <div class="mpro-console-admin__actions">
                            <div class="mpro-console-admin__action-buttons">
                                <?php submit_button( __( 'Save settings', 'mpro-console-log' ), 'primary', 'submit', false ); ?>
                                <button type="button" class="button" data-mpro-restart><?php esc_html_e( 'Restart preview', 'mpro-console-log' ); ?></button>
                            </div>
                            <?php $this->render_plugin_meta(); ?>
                        </div>
                    </div>

                    <aside class="mpro-console-admin__preview-column">
                        <div class="mpro-console-preview-card">
                            <div class="mpro-console-preview-card__topbar">
                                <div>
                                    <span></span><span></span><span></span>
                                </div>
                                <small><?php esc_html_e( 'Live preview', 'mpro-console-log' ); ?></small>
                            </div>
                            <div class="mpro-console-preview-card__canvas">
                                <div class="mpro-console-preview-card__hero-copy">
                                    <span>MPRO / CONSOLE</span>
                                    <strong>Designing systems.<br>Shipping useful products.</strong>
                                </div>
                                <?php echo $this->render_console( $settings, $lines, '', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </div>
                        </div>
                    </aside>
                </div>
            </form>
        </div>
        <?php
    }

    private function number_field( $key, $label, $value, $min, $max, $step, $suffix ) {
        ?>
        <label class="mpro-console-field">
            <span><?php echo esc_html( $label ); ?></span>
            <div class="mpro-console-input-suffix">
                <input
                    type="number"
                    name="mpro_console[<?php echo esc_attr( $key ); ?>]"
                    value="<?php echo esc_attr( $value ); ?>"
                    min="<?php echo esc_attr( $min ); ?>"
                    max="<?php echo esc_attr( $max ); ?>"
                    step="<?php echo esc_attr( $step ); ?>"
                    class="small-text"
                    data-mpro-setting="<?php echo esc_attr( $key ); ?>"
                >
                <?php if ( $suffix ) : ?><em><?php echo esc_html( $suffix ); ?></em><?php endif; ?>
            </div>
        </label>
        <?php
    }

    private function text_field( $key, $label, $value, $placeholder ) {
        ?>
        <label class="mpro-console-field">
            <span><?php echo esc_html( $label ); ?></span>
            <input
                type="text"
                name="mpro_console[<?php echo esc_attr( $key ); ?>]"
                value="<?php echo esc_attr( $value ); ?>"
                placeholder="<?php echo esc_attr( $placeholder ); ?>"
                class="regular-text"
                data-mpro-setting="<?php echo esc_attr( $key ); ?>"
            >
        </label>
        <?php
    }

    private function checkbox_field( $key, $label, $checked ) {
        ?>
        <label class="mpro-console-toggle">
            <input type="checkbox" name="mpro_console[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( $checked, 1 ); ?> data-mpro-setting="<?php echo esc_attr( $key ); ?>">
            <span><?php echo esc_html( $label ); ?></span>
        </label>
        <?php
    }
}

MPRO_Console_Log::instance();
