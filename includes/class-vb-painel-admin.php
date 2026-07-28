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
		add_action( 'admin_bar_menu', array( $this, 'replace_wp_logo' ), 11 );
		add_action( 'load-index.php', array( $this, 'redirect_default_dashboard' ) );
		add_filter( 'login_redirect', array( $this, 'login_redirect' ), 10, 3 );
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
		$wp_admin_bar->remove_node( 'wp-logo' );

		$title = sprintf(
			'<span class="ab-icon" aria-hidden="true"></span><span class="ab-label">%s</span>',
			esc_html__( 'Painel de Configurações', 'valle-branco-painel' )
		);

		$wp_admin_bar->add_node(
			array(
				'id'    => 'vb-painel-brand',
				'title' => $title,
				'href'  => admin_url( 'admin.php?page=' . self::PAGE_SLUG ),
				'meta'  => array(
					'class' => 'vb-admin-bar-brand',
					'title' => __( 'Ir para o Painel de Configurações', 'valle-branco-painel' ),
				),
			)
		);
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
			array(),
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
			array(),
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
				'id'          => 'onde-encontrar',
				'title'       => __( 'Mapa', 'valle-branco-painel' ),
				'description' => __( 'Gerencie estabelecimentos, produtos no mapa e pontos de venda.', 'valle-branco-painel' ),
				'icon'        => 'dashicons-location-alt',
				'url'         => admin_url( 'admin.php?page=vb-onde-encontrar' ),
				'cta'         => __( 'Abrir mapa', 'valle-branco-painel' ),
				'count'       => $this->count_published( 'vb_estabelecimento' ),
				'count_label' => __( 'estabelecimentos', 'valle-branco-painel' ),
				'cap'         => 'manage_options',
			),
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
				'id'          => 'usuarios',
				'title'       => __( 'Usuários', 'valle-branco-painel' ),
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
