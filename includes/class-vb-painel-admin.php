<?php
/**
 * Menu, assets e render do dashboard.
 *
 * @package ValleBrancoPainel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe VB_Painel_Admin
 */
class VB_Painel_Admin {

	const PAGE_SLUG = 'vb-painel';

	/**
	 * Hooks.
	 */
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_menu', array( $this, 'hide_default_dashboard' ), 999 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_global' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_admin_bar_front' ) );
		add_action( 'init', array( $this, 'disable_default_wp_logo' ), 20 );
		// Prioridade baixa = entra cedo na barra (canto esquerdo).
		add_action( 'admin_bar_menu', array( $this, 'replace_wp_logo' ), 0 );
		add_action( 'wp_before_admin_bar_render', array( $this, 'force_brand_first' ), 0 );
		add_action( 'load-index.php', array( $this, 'redirect_default_dashboard' ) );
		add_filter( 'login_redirect', array( $this, 'login_redirect' ), 10, 3 );
	}

	/**
	 * Impede o menu padrão do logo WP de ser registrado.
	 */
	public function disable_default_wp_logo() {
		remove_action( 'admin_bar_menu', 'wp_admin_bar_wp_menu', 10 );
	}

	/**
	 * Menu no topo do painel.
	 */
	public function register_menu() {
		add_menu_page(
			__( 'Painel de Configurações', 'valle-branco-painel' ),
			__( 'Painel', 'valle-branco-painel' ),
			'edit_posts',
			self::PAGE_SLUG,
			array( $this, 'render_page' ),
			'dashicons-admin-home',
			1
		);
	}

	/**
	 * Remove o Dashboard nativo (substituído pelo Painel de Configurações).
	 */
	public function hide_default_dashboard() {
		if ( current_user_can( 'edit_posts' ) ) {
			remove_menu_page( 'index.php' );
		}
	}

	/**
	 * Troca o logo do WordPress na barra superior pelo texto do painel.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar.
	 */
	public function replace_wp_logo( $wp_admin_bar ) {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$wp_admin_bar->remove_node( 'wp-logo' );

		$title = sprintf(
			'<span class="ab-icon" aria-hidden="true"></span><span class="ab-label">%s</span>',
			esc_html__( 'Painel de Configurações', 'valle-branco-painel' )
		);

		$wp_admin_bar->add_node(
			array(
				'id'     => 'vb-painel-brand',
				'parent' => false,
				'title'  => $title,
				'href'   => admin_url( 'admin.php?page=' . self::PAGE_SLUG ),
				'meta'   => array(
					'class' => 'vb-admin-bar-brand',
					'title' => __( 'Ir para o Painel de Configurações', 'valle-branco-painel' ),
				),
			)
		);

		$this->move_brand_to_first( $wp_admin_bar );
	}

	/**
	 * Garante marca no lugar do logo WP e em primeiro na barra.
	 */
	public function force_brand_first() {
		global $wp_admin_bar;

		if ( ! is_user_logged_in() || ! ( $wp_admin_bar instanceof WP_Admin_Bar ) ) {
			return;
		}

		$wp_admin_bar->remove_node( 'wp-logo' );

		if ( ! $wp_admin_bar->get_node( 'vb-painel-brand' ) ) {
			$this->replace_wp_logo( $wp_admin_bar );
			return;
		}

		$this->move_brand_to_first( $wp_admin_bar );
	}

	/**
	 * Coloca o nó da marca como primeiro item da barra.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar.
	 */
	private function move_brand_to_first( $wp_admin_bar ) {
		if ( ! $wp_admin_bar->get_node( 'vb-painel-brand' ) ) {
			return;
		}

		try {
			$ref  = new ReflectionClass( $wp_admin_bar );
			$prop = $ref->getProperty( 'nodes' );
			$prop->setAccessible( true );
			$nodes = $prop->getValue( $wp_admin_bar );

			if ( ! is_array( $nodes ) || ! isset( $nodes['vb-painel-brand'] ) ) {
				return;
			}

			$brand = $nodes['vb-painel-brand'];
			unset( $nodes['vb-painel-brand'] );
			$nodes = array_merge( array( 'vb-painel-brand' => $brand ), $nodes );
			$prop->setValue( $wp_admin_bar, $nodes );
		} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// CSS de fallback mantém a ordem visual.
		}
	}

	/**
	 * Redireciona o Dashboard padrão do WP para o Painel de Configurações.
	 */
	public function redirect_default_dashboard() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['page'] ) ) {
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
		exit;
	}

	/**
	 * Após login, envia para o Painel de Configurações.
	 *
	 * @param string           $redirect_to URL destino.
	 * @param string           $requested   URL pedida.
	 * @param WP_User|WP_Error $user        Usuário.
	 * @return string
	 */
	public function login_redirect( $redirect_to, $requested, $user ) {
		if ( is_wp_error( $user ) || ! ( $user instanceof WP_User ) ) {
			return $redirect_to;
		}

		if ( user_can( $user, 'edit_posts' ) ) {
			return admin_url( 'admin.php?page=' . self::PAGE_SLUG );
		}

		return $redirect_to;
	}

	/**
	 * CSS da barra superior em todo o admin.
	 */
	public function enqueue_global() {
		wp_enqueue_style(
			'vb-painel-admin-bar',
			VB_PAINEL_URL . 'admin/css/admin-bar.css',
			array( 'admin-bar' ),
			VB_PAINEL_VERSION
		);
	}

	/**
	 * CSS da barra superior no front (quando logado).
	 */
	public function enqueue_admin_bar_front() {
		if ( ! is_admin_bar_showing() ) {
			return;
		}

		wp_enqueue_style(
			'vb-painel-admin-bar',
			VB_PAINEL_URL . 'admin/css/admin-bar.css',
			array( 'admin-bar' ),
			VB_PAINEL_VERSION
		);
	}

	/**
	 * CSS só na página do painel.
	 *
	 * @param string $hook Hook da tela.
	 */
	public function enqueue( $hook ) {
		if ( 'toplevel_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style(
			'vb-painel-admin',
			VB_PAINEL_URL . 'admin/css/admin.css',
			array( 'dashicons' ),
			VB_PAINEL_VERSION
		);
	}

	/**
	 * Renderiza a view.
	 */
	public function render_page() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Você não tem permissão para acessar esta página.', 'valle-branco-painel' ) );
		}

		$user     = wp_get_current_user();
		$nome     = $user->display_name ? $user->display_name : $user->user_login;
		$saudacao = $this->get_saudacao();
		$cards    = $this->get_cards();

		include VB_PAINEL_PATH . 'admin/views/dashboard.php';
	}

	/**
	 * Saudação conforme horário.
	 *
	 * @return string
	 */
	private function get_saudacao() {
		$hora = (int) current_time( 'G' );

		if ( $hora < 12 ) {
			return __( 'Bom dia', 'valle-branco-painel' );
		}
		if ( $hora < 18 ) {
			return __( 'Boa tarde', 'valle-branco-painel' );
		}
		return __( 'Boa noite', 'valle-branco-painel' );
	}

	/**
	 * Cards do dashboard com contagens ao vivo.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function get_cards() {
		$cards = array(
			array(
				'id'          => 'hero-sliders',
				'title'       => __( 'Banners', 'valle-branco-painel' ),
				'description' => __( 'Configure os banners principais da home e de outras páginas.', 'valle-branco-painel' ),
				'icon'        => 'dashicons-images-alt2',
				'url'         => admin_url( 'edit.php?post_type=hbs_slider' ),
				'cta'         => __( 'Gerenciar banners', 'valle-branco-painel' ),
				'count'       => $this->count_published( 'hbs_slider' ),
				'count_label' => __( 'banners', 'valle-branco-painel' ),
				'cap'         => 'edit_posts',
			),
			array(
				'id'          => 'produtos',
				'title'       => __( 'Produtos', 'valle-branco-painel' ),
				'description' => __( 'Cadastre fichas de produtos, imagens e informações exibidas no site.', 'valle-branco-painel' ),
				'icon'        => 'dashicons-products',
				'url'         => admin_url( 'edit.php?post_type=vb_produto' ),
				'cta'         => __( 'Gerenciar produtos', 'valle-branco-painel' ),
				'count'       => $this->count_published( 'vb_produto' ),
				'count_label' => __( 'produtos', 'valle-branco-painel' ),
				'cap'         => 'edit_posts',
			),
			array(
				'id'          => 'posts',
				'title'       => __( 'Artigos/Receitas', 'valle-branco-painel' ),
				'description' => __( 'Publique e edite artigos, receitas e conteúdos do site.', 'valle-branco-painel' ),
				'icon'        => 'dashicons-admin-post',
				'url'         => admin_url( 'edit.php' ),
				'cta'         => __( 'Gerenciar artigos', 'valle-branco-painel' ),
				'count'       => $this->count_published( 'post' ),
				'count_label' => __( 'publicados', 'valle-branco-painel' ),
				'cap'         => 'edit_posts',
			),
			array(
				'id'          => 'faq',
				'title'       => __( 'FAQ', 'valle-branco-painel' ),
				'description' => __( 'Gerencie perguntas, respostas e categorias da FAQ do site.', 'valle-branco-painel' ),
				'icon'        => 'dashicons-editor-help',
				'url'         => admin_url( 'edit.php?post_type=pf_faq' ),
				'cta'         => __( 'Gerenciar FAQ', 'valle-branco-painel' ),
				'count'       => $this->count_published( 'pf_faq' ),
				'count_label' => __( 'perguntas', 'valle-branco-painel' ),
				'cap'         => 'edit_posts',
			),
			array(
				'id'          => 'onde-encontrar',
				'title'       => __( 'Mapa', 'valle-branco-painel' ),
				'description' => __( 'Gerencie estabelecimentos, produtos no mapa e pontos de venda.', 'valle-branco-painel' ),
				'icon'        => 'dashicons-location-alt',
				'url'         => admin_url( 'admin.php?page=vb-onde-encontrar' ),
				'cta'         => __( 'Abrir mapa', 'valle-branco-painel' ),
				'count'       => $this->count_cidades_ativas_mapa(),
				'count_label' => __( 'cidades ativas', 'valle-branco-painel' ),
				'cap'         => 'manage_options',
			),
			array(
				'id'          => 'usuarios',
				'title'       => __( 'Usuários do Site', 'valle-branco-painel' ),
				'description' => __( 'Adicione, edite e gerencie quem tem acesso ao painel do site.', 'valle-branco-painel' ),
				'icon'        => 'dashicons-groups',
				'url'         => admin_url( 'users.php' ),
				'cta'         => __( 'Gerenciar usuários', 'valle-branco-painel' ),
				'count'       => $this->count_users_total(),
				'count_label' => __( 'usuários', 'valle-branco-painel' ),
				'cap'         => 'list_users',
			),
		);

		// Apenas administradores veem atualizações do sistema.
		if ( current_user_can( 'manage_options' ) ) {
			$updates_count = $this->count_updates();
			$cards[]       = array(
				'id'          => 'atualizacoes',
				'title'       => __( 'Atualizações do sistema', 'valle-branco-painel' ),
				'description' => __( 'Verifique e aplique atualizações do WordPress, plugins e temas.', 'valle-branco-painel' ),
				'icon'        => 'dashicons-update',
				'url'         => admin_url( 'update-core.php' ),
				'cta'         => __( 'Ver atualizações', 'valle-branco-painel' ),
				'count'       => $updates_count,
				'count_label' => _n( 'pendente', 'pendentes', $updates_count, 'valle-branco-painel' ),
				'cap'         => 'manage_options',
			);
		}

		$visible = array();
		foreach ( $cards as $card ) {
			$cap = isset( $card['cap'] ) ? $card['cap'] : 'edit_posts';
			if ( current_user_can( $cap ) ) {
				$visible[] = $card;
			}
		}

		return $visible;
	}

	/**
	 * Conta posts publicados de um tipo.
	 *
	 * @param string $post_type Tipo.
	 * @return int
	 */
	private function count_published( $post_type ) {
		if ( ! post_type_exists( $post_type ) ) {
			return 0;
		}

		$counts = wp_count_posts( $post_type );
		if ( ! $counts || ! isset( $counts->publish ) ) {
			return 0;
		}

		return (int) $counts->publish;
	}

	/**
	 * Total de usuários.
	 *
	 * @return int
	 */
	private function count_users_total() {
		$counts = count_users();
		return isset( $counts['total_users'] ) ? (int) $counts['total_users'] : 0;
	}

	/**
	 * Cidades distintas ativas no mapa (mesmo critério do front):
	 * estabelecimento publicado, com lat/lng e produto ativo recente.
	 *
	 * @return int
	 */
	private function count_cidades_ativas_mapa() {
		global $wpdb;

		if ( ! post_type_exists( 'vb_estabelecimento' ) ) {
			return 0;
		}

		$tabela = $wpdb->prefix . 'vb_produto_local';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$existe = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tabela ) );
		if ( $existe !== $tabela ) {
			return 0;
		}

		$where_extra = '';
		$params      = array();

		if ( class_exists( 'VB_OE_Database' ) ) {
			$limite = VB_OE_Database::data_limite_mapa();
			if ( $limite ) {
				$where_extra = ' AND r.data_atualizacao >= %s';
				$params[]    = $limite;
			}
		}

		// Parte da tabela de relações (mais seletiva) e conta cidades distintas.
		$sql = "SELECT COUNT(*) FROM (
				SELECT DISTINCT cidade.meta_value AS cidade
				FROM {$tabela} r
				INNER JOIN {$wpdb->posts} e
					ON e.ID = r.estabelecimento_id
					AND e.post_type = 'vb_estabelecimento'
					AND e.post_status = 'publish'
				INNER JOIN {$wpdb->postmeta} cidade
					ON cidade.post_id = e.ID
					AND cidade.meta_key = '_vb_cidade'
					AND cidade.meta_value <> ''
				INNER JOIN {$wpdb->postmeta} lat
					ON lat.post_id = e.ID
					AND lat.meta_key = '_vb_lat'
					AND lat.meta_value <> ''
				INNER JOIN {$wpdb->postmeta} lng
					ON lng.post_id = e.ID
					AND lng.meta_key = '_vb_lng'
					AND lng.meta_value <> ''
				WHERE r.status = 'ativo'{$where_extra}
			) AS cidades_ativas";

		if ( ! empty( $params ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$count = $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$count = $wpdb->get_var( $sql );
		}

		return (int) $count;
	}

	/**
	 * Total de atualizações disponíveis (core, plugins, temas).
	 *
	 * @return int
	 */
	private function count_updates() {
		if ( ! function_exists( 'wp_get_update_data' ) ) {
			require_once ABSPATH . 'wp-includes/update.php';
		}

		$data = wp_get_update_data();
		if ( isset( $data['counts']['total'] ) ) {
			return (int) $data['counts']['total'];
		}

		return 0;
	}
}
