<?php
class SSP_MetaBox {

	use SSP\Field;

	/**
	 * 外部からのインスタンス化を防ぐ
	 */
	private function __construct() {}

	/**
	 * Post meta
	 */
	const POST_META_KEYS = [
		'robots'      => 'ssp_meta_robots',
		'title'       => 'ssp_meta_title',
		'description' => 'ssp_meta_description',
		'keyword'     => 'ssp_meta_keyword',
	];

	/**
	 * Term meta
	 */
	const TERM_META_KEYS = [
		'robots'      => 'ssp_meta_robots',
		'title'       => 'ssp_meta_title',
		'description' => 'ssp_meta_description',
	];

	/**
	 * @var array Setting choices
	 */
	private static $robots_options = [];


	/**
	 * init
	 */
	public static function init() {

		// Set choices
		self::$robots_options = [
			''                 => __( 'Keep default settings', 'loos-ssp' ), // デフォルト設定のまま
			'index,follow'     => __( 'Index', 'loos-ssp' ), // インデックスさせる
			'noindex'          => __( 'Don\'t index', 'loos-ssp' ) . '(noindex)', // インデックスさせない
			'nofollow'         => __( 'Don\'t follow links', 'loos-ssp' ) . '(nofollow)', // リンクを辿らせない
			'noarchive'        => __( 'Don\'t cache', 'loos-ssp' ) . '(noarchive)', // キャッシュさせない
			'noindex,nofollow' => 'noindex,nofollow',
		];

		// post meta
		add_action( 'add_meta_boxes', [ 'SSP_MetaBox', 'add_ssp_metabox' ], 1 );
		add_action( 'save_post', [ 'SSP_MetaBox', 'save_post_metas' ] );

		// term meta
		// add_action('category_add_form_fields', [ 'SSP_MetaBox', 'add_term_fields' ]);
		// add_action('post_tag_add_form_fields', [ 'SSP_MetaBox', 'add_term_fields' ]);
		add_action( 'category_edit_form_fields', [ 'SSP_MetaBox', 'add_term_edit_fields' ], 20 );
		add_action( 'post_tag_edit_form_fields', [ 'SSP_MetaBox', 'add_term_edit_fields' ], 20 );
		// add_action( 'created_term', [ 'SSP_MetaBox', 'save_term_metas' ] );  // 新規追加用 保存処理フック
		add_action( 'edited_terms', [ 'SSP_MetaBox', 'save_term_metas' ] );   // 編集ページ用 保存処理フック
	}


	/**
	 * Add metabox.
	 */
	public static function add_ssp_metabox() {
		$args       = [
			'public'   => true,
			'_builtin' => false,
		];
		$post_types = get_post_types( $args, 'names', 'and' );
		$screens    = array_merge( ['post', 'page' ], $post_types );

		add_meta_box(
			'ssp_metabox',                            // メタボックスのID名(html)
			__( 'SEO SIMPLE PACK Settings', 'loos-ssp' ),  // メタボックスのタイトル
			['SSP_MetaBox', 'ssp_metabox_callback' ], // htmlを出力する関数名
			$screens,                                 // 表示する投稿タイプ
			'normal',                                 // 表示場所 : 'normal', 'advanced', 'side'
			'default',                                // 表示優先度 : 'high', 'core', 'default' または 'low'
			null                                      // $callback_args
		);
	}


	/**
	 * Metabox cintents.
	 * memo: privateにするとエラーが起きる
	 */
	public static function ssp_metabox_callback( $post ) {

		$val_robots      = get_post_meta( $post->ID, self::POST_META_KEYS['robots'], true );
		$val_title       = get_post_meta( $post->ID, self::POST_META_KEYS['title'], true );
		$val_description = get_post_meta( $post->ID, self::POST_META_KEYS['description'], true );
		$val_keyword     = get_post_meta( $post->ID, self::POST_META_KEYS['keyword'], true );

		// 更新に伴う調節
		if ( 'noindex,follow' === $val_robots ) {
			update_post_meta( $post->ID, self::POST_META_KEYS['robots'], 'noindex' );
			$val_robots = 'noindex';
		} elseif ( 'index,nofollow' === $val_robots ) {
			update_post_meta( $post->ID, self::POST_META_KEYS['robots'], 'nofollow' );
			$val_robots = 'nofollow';
		}

		$ssp_page_url    = admin_url( 'admin.php?page=ssp_main_setting' );
		$ssp_page_url_pt = admin_url( 'admin.php?page=ssp_main_setting#post_type' );
		$help_page_url   = admin_url( 'admin.php?page=ssp_help' );
	?>
		<div id="ssp_wrap" class="ssp_metabox -post">
		<?php
			// robots
			self::output_field( self::POST_META_KEYS['robots'], [
				'title'       => __( 'Overwrite setting of "robots" tag(Index setting)', 'loos-ssp' ),
				'type'        => 'select',
				'choices'     => self::$robots_options,
				'desc'        => sprintf(
					__( 'If you want to know the default settings, see %s.', 'loos-ssp' ),
					'<a href="' . esc_url( $ssp_page_url_pt ) . '" target="_blank">' . __( '"Post page" tab in "General Settings"', 'loos-ssp' ) . '</a>'
				),
			], $val_robots );

			// title
			self::output_field( self::POST_META_KEYS['title'], [
				'title'       => __( 'Title tag of this page', 'loos-ssp' ),
				'desc'        => sprintf(
					__( '%s is available.', 'loos-ssp' ),
					'<a href="' . esc_url( $help_page_url ) . '" target="_blank">' . __( 'Snippet tags', 'loos-ssp' ) . '</a>'
				),
			], $val_title );

			// description
			self::output_field( self::POST_META_KEYS['description'], [
				'title'       => __( 'Description of this page', 'loos-ssp' ),
				'type'        => 'textarea',
				'desc'        => __( 'If blank, a description tag will be automatically generated from the content.', 'loos-ssp' ),
			], $val_description );

			// keywords
			self::output_field( self::POST_META_KEYS['keyword'], [
				'title'       => __( 'Keywords of this page', 'loos-ssp' ),
				'desc'        => sprintf(
					__( 'If blank, the "Keyword" setting of %s is used.', 'loos-ssp' ),
					'<a href="' . esc_url( $ssp_page_url ) . '" target="_blank">' . __( '"Basic settings"', 'loos-ssp' ) . '</a>'
				),
			], $val_keyword );
		?>
		<div>
	<?php
		// Set nonce field
		wp_nonce_field( SSP_Data::NONCE_ACTION, SSP_Data::NONCE_NAME );
	}


	/**
	 * Save post meta
	 */
	public static function save_post_metas( $post_id ) {

		// 新規投稿ページでも発動するので、$_POSTが空なら return
		if ( empty( $_POST ) ) return;

		// 自動保存時
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

		// nonceキー存在チェック
		if ( ! isset( $_POST[ SSP_Data::NONCE_NAME ] ) ) return;

		// nonceの検証
		$nonce_name = $_POST[ SSP_Data::NONCE_NAME ]; // phpcs:ignore
		if ( ! wp_verify_nonce( $nonce_name, SSP_Data::NONCE_ACTION ) ) return;

		// Check the user's permissions. 現在のユーザーに編集権限があるかのチェック
		$post_type     = isset( $_POST['post_type'] ) ? $_POST['post_type'] : ''; // phpcs:ignorex
		$check_can_key = 'page' === $post_type ? 'edit_page' : 'edit_post';
		if ( ! current_user_can( $check_can_key, $post_id ) ) {
			return;
		}

		foreach ( self::POST_META_KEYS as $key => $meta_key ) {

			// 保存したい情報が渡ってきているか確認
			if ( ! isset( $_POST[ $meta_key ] ) ) return;

			// 入力された値をサニタイズ
			$meta_val = sanitize_text_field( $_POST[ $meta_key ] ); // phpcs:ignorex

			// 値を保存
			update_post_meta( $post_id, $meta_key, $meta_val );

		}

	}


	/**
	 * ターム「編集」画面にフィールド追加
	 */
	public static function add_term_edit_fields( $term ) {
		$val_robots      = get_term_meta( $term->term_id, self::TERM_META_KEYS['robots'], true );
		$val_title       = get_term_meta( $term->term_id, self::TERM_META_KEYS['title'], true );
		$val_description = get_term_meta( $term->term_id, self::TERM_META_KEYS['description'], true );

		// @codingStandardsIgnoreStart
	?>
		<tr class="ssp_term_meta_title">
			<td colspan="2">
				<h2>SEO SIMPLE PACKの設定</h2>
			</td>
		</tr>
		<tr class="form-field">
			<th>
				<label for="<?=self::TERM_META_KEYS['robots']?>">
					<?php esc_html_e( '"robots" tag of this term page', 'loos-ssp' ); ?>
				</label>
			</th>
			<td>
				<?php self::select_box( self::TERM_META_KEYS['robots'], $val_robots, self::$robots_options ) ?>
			</td>
		</tr>
		<tr class="form-field">
			<th>
				<label for="<?=self::TERM_META_KEYS['title']?>">
					<?php esc_html_e( 'Title tag of this term page', 'loos-ssp' ); ?>
				</label>
			</th>
			<td>
				<?php self::text_input( self::TERM_META_KEYS['title'], $val_title ) ?>
			</td>
		</tr>
		<tr class="form-field">
			<th>
				<label for="<?=self::TERM_META_KEYS['description']?>">
					<?php esc_html_e( 'Description of this term page', 'loos-ssp' ); ?>
				</label>
			</th>
			<td>
				<?php self::textarea( self::TERM_META_KEYS['description'], $val_description ) ?>
			</td>
		</tr>
	<?php
		// @codingStandardsIgnoreEnd

		// Set nonce field
		wp_nonce_field( SSP_Data::NONCE_ACTION, SSP_Data::NONCE_NAME );
	}


	/**
	 * Save term meta
	 */
	public static function save_term_metas( $term_id ) {

		// $_POSTが空なら return
		if ( empty( $_POST ) ) return;

		// nonceキー存在チェック
		if ( ! isset( $_POST[ SSP_Data::NONCE_NAME ] ) ) return;

		// nonceの検証
		$nonce_name = $_POST[ SSP_Data::NONCE_NAME ]; // phpcs:ignore
		if ( ! wp_verify_nonce( $nonce_name, SSP_Data::NONCE_ACTION ) ) return;

		foreach ( self::TERM_META_KEYS as $key => $meta_key ) {

			// 保存したい情報が渡ってきているか確認
			if ( ! isset( $_POST[ $meta_key ] ) ) return;

			// 入力された値をサニタイズ
			$meta_val = sanitize_text_field( $_POST[ $meta_key ] ); // phpcs:ignorex

			// 値を保存
			update_term_meta( $term_id, $meta_key, $meta_val );

		}
	}
}
