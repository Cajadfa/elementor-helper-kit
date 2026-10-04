<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EHK_JSON_Editor {
	const SLUG       = 'ehk-json-editor';
	const META       = '_elementor_data';
	const BACKUP     = '_eje_backups';
	const MAX_BACKUP = 5;

	public static function init() {
		$instance = new self();
		$instance->hooks();
	}

	private function hooks() {
		add_action( 'admin_menu', array( $this, 'register_page' ) );
		add_filter( 'page_row_actions', array( $this, 'row_action' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'row_action' ), 10, 2 );
		add_action( 'add_meta_boxes', array( $this, 'metabox' ) );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar' ), 100 );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'wp_ajax_ehk_json_save', array( $this, 'ajax_save' ) );
	}

	private function url( $post_id ) {
		return admin_url( 'admin.php?page=' . self::SLUG . '&post=' . absint( $post_id ) );
	}

	private function has_elementor( $post_id ) {
		return (bool) get_post_meta( $post_id, self::META, true );
	}

	public function register_page() {
		add_submenu_page(
			null,
			'Elementor JSON',
			'Elementor JSON',
			'edit_posts',
			self::SLUG,
			array( $this, 'render' )
		);
	}

	public function row_action( $actions, $post ) {
		if ( current_user_can( 'edit_post', $post->ID ) && $this->has_elementor( $post->ID ) ) {
			$actions['ehk_json'] = '<a href="' . esc_url( $this->url( $post->ID ) ) . '">ویرایش JSON المنتور</a>';
		}
		return $actions;
	}

	public function metabox() {
		global $post;

		if ( $post && $this->has_elementor( $post->ID ) ) {
			add_meta_box(
				'ehk_json_box',
				'Elementor JSON',
				function ( $post ) {
					echo '<a class="button button-primary" style="width:100%;text-align:center" href="' . esc_url( $this->url( $post->ID ) ) . '">ویرایش سورس JSON</a>';
				},
				null,
				'side',
				'high'
			);
		}
	}

	public function admin_bar( $bar ) {
		if ( is_admin() || ! is_singular() ) {
			return;
		}

		$post_id = get_queried_object_id();
		if ( current_user_can( 'edit_post', $post_id ) && $this->has_elementor( $post_id ) ) {
			$bar->add_node(
				array(
					'id'    => 'ehk-json-editor',
					'title' => 'Elementor JSON',
					'href'  => $this->url( $post_id ),
				)
			);
		}
	}

	public function assets( $hook ) {
		if ( empty( $_GET['page'] ) || self::SLUG !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}

		$settings = wp_enqueue_code_editor(
			array(
				'type'       => 'application/json',
				'codemirror' => array(
					'lineWrapping' => false,
					'foldGutter'   => true,
					'gutters'      => array( 'CodeMirror-lint-markers', 'CodeMirror-foldgutter' ),
				),
			)
		);

		wp_enqueue_style( 'ehk-json-editor', EHK_URL . 'assets/editor.css', array(), EHK_VERSION );
		wp_enqueue_script( 'ehk-json-editor', EHK_URL . 'assets/editor.js', array( 'jquery', 'code-editor' ), EHK_VERSION, true );

		wp_localize_script(
			'ehk-json-editor',
			'EHK_JSON',
			array(
				'ajax'     => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'ehk_json_save' ),
				'post'     => absint( isset( $_GET['post'] ) ? $_GET['post'] : 0 ),
				'settings' => $settings,
			)
		);
	}

	public function render() {
		$post_id = absint( isset( $_GET['post'] ) ? $_GET['post'] : 0 );
		$post    = get_post( $post_id );

		if ( ! $post || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'دسترسی ندارید یا برگه پیدا نشد.', 'elementor-helper-kit' ) );
		}

		$raw  = get_post_meta( $post_id, self::META, true );
		$data = json_decode( is_string( $raw ) ? $raw : wp_json_encode( $raw ), true );
		$json = null !== $data
			? wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
			: (string) $raw;

		$backups = get_post_meta( $post_id, self::BACKUP, true );
		$backups = is_array( $backups ) ? $backups : array();
		?>
		<div class="wrap ehk-json-wrap">
			<h1>JSON المنتور: <?php echo esc_html( get_the_title( $post ) ); ?></h1>
			<div class="ehk-json-toolbar">
				<button class="button button-primary" id="ehk-json-save">ذخیره (Ctrl+S)</button>
				<button class="button" id="ehk-json-format">مرتب‌سازی</button>
				<button class="button" id="ehk-json-minify">فشرده‌سازی</button>
				<?php if ( $backups ) : ?>
					<select id="ehk-json-backup">
						<option value="">— بارگذاری بکاپ —</option>
						<?php foreach ( array_reverse( $backups, true ) as $index => $backup ) : ?>
							<option value="<?php echo esc_attr( $index ); ?>"><?php echo esc_html( date_i18n( 'Y-m-d H:i:s', $backup['time'] ) ); ?></option>
						<?php endforeach; ?>
					</select>
				<?php endif; ?>
				<a class="button" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( get_permalink( $post ) ); ?>">مشاهده برگه</a>
				<a class="button" href="<?php echo esc_url( admin_url( 'post.php?post=' . $post_id . '&action=elementor' ) ); ?>">باز کردن در المنتور</a>
				<span id="ehk-json-status"></span>
			</div>
			<textarea id="ehk-json-code" dir="ltr"><?php echo esc_textarea( $json ); ?></textarea>
			<script type="application/json" id="ehk-json-backups"><?php
				echo wp_json_encode(
					array_map(
						function ( $backup ) {
							return isset( $backup['data'] ) ? $backup['data'] : '';
						},
						$backups
					)
				);
			?></script>
		</div>
		<?php
	}

	public function ajax_save() {
		check_ajax_referer( 'ehk_json_save', 'nonce' );

		$post_id = absint( isset( $_POST['post'] ) ? $_POST['post'] : 0 );
		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( 'دسترسی ندارید.', 403 );
		}

		$json = wp_unslash( isset( $_POST['json'] ) ? $_POST['json'] : '' );
		$data = json_decode( $json, true );

		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $data ) ) {
			wp_send_json_error( 'JSON نامعتبر: ' . json_last_error_msg() );
		}

		$old     = get_post_meta( $post_id, self::META, true );
		$backups = get_post_meta( $post_id, self::BACKUP, true );
		$backups = is_array( $backups ) ? $backups : array();
		$backups[] = array(
			'time' => current_time( 'timestamp' ),
			'data' => $old,
		);
		$backups = array_slice( $backups, -self::MAX_BACKUP );

		update_post_meta( $post_id, self::BACKUP, wp_slash( $backups ) );
		update_post_meta( $post_id, self::META, wp_slash( wp_json_encode( $data ) ) );

		$this->clear_cache( $post_id );
		wp_send_json_success( 'ذخیره شد ✔' );
	}

	private function clear_cache( $post_id ) {
		foreach ( array( '_elementor_css', '_elementor_page_assets', '_elementor_element_cache' ) as $key ) {
			delete_post_meta( $post_id, $key );
		}

		if ( class_exists( '\\Elementor\\Core\\Files\\CSS\\Post' ) ) {
			try {
				\Elementor\Core\Files\CSS\Post::create( $post_id )->delete();
			} catch ( \Throwable $error ) {
				// Elementor cache clearing is best effort.
			}
		}

		if ( class_exists( '\\Elementor\\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
			try {
				\Elementor\Plugin::$instance->files_manager->clear_cache();
			} catch ( \Throwable $error ) {
				// Elementor cache clearing is best effort.
			}
		}

		clean_post_cache( $post_id );
	}
}
