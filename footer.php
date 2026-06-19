<?php
$k26_whys = get_option( 'karavela_whys_', [] )['whys'] ?? [];
if ( ! empty( $k26_whys ) && ! is_singular( 'trip' ) ) :
?>
<div class="why">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <h2 class="why__title">Proč s Karavelou?</h2>
            </div>
            <?php $why_counter = 0; foreach ( $k26_whys as $why ) : $why_counter++; ?>
            <div class="col-sm-3">
                <span class="why__number"><?php echo esc_html( $why['number'] ); ?></span>
                <span class="why__text"><?php echo esc_html( $why['text'] ); ?></span>
            </div>
            <?php if ( $why_counter >= 4 ) break; endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

      <footer class="footer">

        <div class="brown">
          <div class="container">
            <div class="row">
              <div class="col-sm-3 col-first">
                <h2>Užitečné</h2>
                <?php wp_nav_menu( array(
                    'theme_location' => 'usefull_links',
                    'menu_class'     => 'brown-list',
                    'walker'         => new K26_Footer_Menu_Walker(),
                    'fallback_cb'    => false,
                ) ); ?>
              </div>
              <div class="col-sm-3 col-second">
                <h2>Proč s námi</h2>
                <?php wp_nav_menu( array(
                    'theme_location' => 'why_links',
                    'menu_class'     => 'brown-list',
                    'walker'         => new K26_Footer_Menu_Walker(),
                    'fallback_cb'    => false,
                ) ); ?>
                <a href="https://www.click2claim.eu/ap/karavela" class="claim-banner" target="_blank" rel="noopener">
                  <img src="https://karavela.cz/cz07.jpg" alt="Click2Claim – pojištění zpoždění letu">
                </a>
              </div>
              <div class="col-sm-3 col-three">
                <h2>Partneři</h2>
                <a href="https://www.merinoobchod.cz" class="brown-list-item__link">
                  <img src="https://www.karavela.cz/wp-content/uploads/2023/01/merinoobchod.jpg" class="brown-list-item__link-img" alt="Merino obchod">
                </a>
                <a href="https://www.outdoorshops.cz" class="brown-list-item__link">
                  <img src="https://www.karavela.cz/wp-content/uploads/2023/01/outdoorshops.png" class="brown-list-item__link-img" alt="Outdoor shops">
                </a>
                <a href="https://www.hudy.cz/" class="brown-list-item__link">
                  <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/img/hudy.png" class="brown-list-item__link-img" alt="Hudy">
                </a>
                <a href="https://www.karavela.cz/certifikat/" class="brown-list-item__link" rel="noopener">
                  <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/img/ck_karavela_clensky_list_small.png" class="brown-list-item__link-img" alt="Členský list">
                </a>
                <a href="https://www.thenorthface.cz" class="brown-list-item__link" target="_blank" rel="noopener">
                  <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/img/thenorthface.png" class="brown-list-item__link-img" alt="The North Face">
                </a>
              </div>
              <div class="col-sm-3 col-four">
                <h2>Kontakt</h2>
                <p>CK Karavela s.r.o.<br>K Netlukám 1472/2<br>104 00 Praha 10</p>
                <p><a class="tel" href="tel:+420722778088">722 778 088</a></p>
                <p><a href="mailto:karavela@karavela.cz">karavela@karavela.cz</a></p>
              </div>
            </div>
          </div>
        </div>

        <div class="dark">
          <div class="container">
            <div class="row">
              <div class="col-sm-12">
                <p>© Copyright <?php echo esc_html( date( 'Y' ) ); ?> K Netlukám 1472/2, Praha 10<br>
                Firma zapsána v obchodním rejstříku Krajského soudu v Hradci Králové dne 22.8.2012; oddíl C, vložka 31087 a v živnostenském rejstříku MÚ Praha 22</p>
              </div>
            </div>
          </div>
        </div>

      </footer>

    <div id="search">
      <button type="button" class="close">×</button>
      <form method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
        <input type="search" name="s" value="" placeholder="Vyhledat zájezd">
        <button type="submit" class="btn btn-primary">Vyhledat</button>
      </form>
    </div>

<?php wp_footer(); ?>
</body>
</html>
