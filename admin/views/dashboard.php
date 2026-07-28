<?php
/**
 * View: dashboard do Painel Valle.
 *
 * @package ValleBrancoPainel
 *
 * @var string $saudacao
 * @var string $nome
 * @var array  $cards
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$data_hoje = date_i18n( 'l, j \d\e F \d\e Y' );
?>
<div class="wrap vb-painel">
	<section class="vb-painel__intro" aria-labelledby="vb-painel-atalhos">
		<h2 id="vb-painel-atalhos" class="vb-painel__section-title">
			<?php esc_html_e( 'Configurações principais', 'valle-branco-painel' ); ?>
		</h2>
		<p class="vb-painel__section-lead">
			<?php esc_html_e( 'Escolha uma área para gerenciar o conteúdo do site. Os números nos cards são atualizados automaticamente.', 'valle-branco-painel' ); ?>
		</p>
	</section>

	<div class="vb-painel__welcome">
		<div class="vb-painel__orbs" aria-hidden="true">
			<span class="vb-painel__orb vb-painel__orb--1"></span>
			<span class="vb-painel__orb vb-painel__orb--2"></span>
			<span class="vb-painel__orb vb-painel__orb--3"></span>
			<span class="vb-painel__orb vb-painel__orb--4"></span>
			<span class="vb-painel__orb vb-painel__orb--5"></span>
			<span class="vb-painel__orb vb-painel__orb--ring"></span>
		</div>
		<div class="vb-painel__welcome-inner">
			<p class="vb-painel__eyebrow"><?php esc_html_e( 'Painel Valle Branco', 'valle-branco-painel' ); ?></p>
			<h1 class="vb-painel__hello">
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: saudação, 2: nome do usuário */
						__( '%1$s, %2$s', 'valle-branco-painel' ),
						$saudacao,
						$nome
					)
				);
				?>
			</h1>
			<p class="vb-painel__welcome-text">
				<?php esc_html_e( 'Aqui você encontra os atalhos das principais configurações do site.', 'valle-branco-painel' ); ?>
			</p>
			<p class="vb-painel__date">
				<span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
				<?php echo esc_html( $data_hoje ); ?>
			</p>
		</div>
	</div>

	<section class="vb-painel__section">
		<div class="vb-painel__grid">
			<?php foreach ( $cards as $card ) : ?>
				<article class="vb-painel-card vb-painel-card--<?php echo esc_attr( $card['id'] ); ?>">
					<div class="vb-painel-card__top">
						<span class="vb-painel-card__icon" aria-hidden="true">
							<span class="dashicons <?php echo esc_attr( $card['icon'] ); ?>"></span>
						</span>
						<?php if ( null !== $card['count'] ) : ?>
							<span class="vb-painel-card__badge">
								<strong><?php echo esc_html( (string) $card['count'] ); ?></strong>
								<?php echo esc_html( $card['count_label'] ); ?>
							</span>
						<?php endif; ?>
					</div>

					<h3 class="vb-painel-card__title"><?php echo esc_html( $card['title'] ); ?></h3>
					<p class="vb-painel-card__desc"><?php echo esc_html( $card['description'] ); ?></p>

					<a class="vb-painel-card__cta" href="<?php echo esc_url( $card['url'] ); ?>">
						<?php echo esc_html( $card['cta'] ); ?>
						<span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
					</a>
				</article>
			<?php endforeach; ?>
		</div>
	</section>
</div>
