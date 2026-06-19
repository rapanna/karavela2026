<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class( 'front' ); ?>>
<?php wp_body_open(); ?>

    <!-- Header -->
    <header class="header">
      <a name="zacatek"></a>
      <div class="container">
        <div class="col-sm-4">
          <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="header-link">
            <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/img/karavela-logo.png" alt="CK Karavela" class="header-link__img">
            <div class="header-link__text">
              <div class="header-link__title">CK Karavela</div>
              <div class="header-link__subtitle">Cesty za poznáním</div>
            </div>
          </a>
          <button type="button" class="header-toggle" aria-controls="header-menu" aria-expanded="false" aria-label="Menu">
            <span class="header-toggle__bars" aria-hidden="true"><span></span><span></span><span></span></span>
          </button>
        </div>
        <div class="col-sm-8">
          <div class="header-menu" id="header-menu">
            <div class="matroska">
              <?php wp_nav_menu( array(
                  'theme_location' => 'header_menu',
                  'menu_class'     => 'header-menu-list',
                  'walker'         => new K26_Header_Menu_Walker(),
                  'fallback_cb'    => false,
              ) ); ?>
            </div>
          </div>
        </div>
      </div>
    </header>

    <div class="kontinent">
      <div class="container">
        <div class="row">
          <div class="col">
            <button type="button" class="kontinent-toggle" aria-controls="menu-kontinenty" aria-expanded="false">
              <span class="kontinent-toggle__bars" aria-hidden="true"><span></span><span></span><span></span></span>
              <span class="kontinent-toggle__label">Kontinenty</span>
            </button>
            <?php wp_nav_menu( array(
                'theme_location' => 'kontinent_menu',
                'menu_class'     => 'kontinent-menu-list nav navbar-nav',
                'container'      => false,
                'walker'         => new K26_Kontinent_Menu_Walker(),
                'fallback_cb'    => false,
            ) ); ?>
            <ul class="nav navbar-nav navbar-right">
              <li class="search"><a href="#search" class="search-button"></a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
