/* ========================================================================
 * Karavela 2026 – vlastní JS šablony
 * (Slick a Bootstrap dropdown se načítají samostatně přes slick.min.js a dropdown.js)
 * ======================================================================== */

jQuery(function () {
    // Mobilní hamburger pro hlavní menu
    jQuery(".header-toggle").on("click", function () {
        var $header = jQuery(this).closest(".header");
        var open = $header.toggleClass("is-open").hasClass("is-open");
        jQuery(this).attr("aria-expanded", open ? "true" : "false");
    });

    // Mobilní hamburger pro kontinenty
    jQuery(".kontinent-toggle").on("click", function () {
        var $bar = jQuery(this).closest(".kontinent");
        var open = $bar.toggleClass("is-open").hasClass("is-open");
        jQuery(this).attr("aria-expanded", open ? "true" : "false");
    });

    // Vyhledávací overlay
    jQuery('a[href="#search"]').on("click", function (event) {
        event.preventDefault();
        jQuery("#search").addClass("open");
        jQuery('#search > form > input[type="search"]').focus();
    });

    jQuery("#search, #search button.close").on("click keyup", function (event) {
        if (
            event.target == this ||
            event.target.className == "close" ||
            event.keyCode == 27
        ) {
            jQuery(this).removeClass("open");
        }
    });
});
