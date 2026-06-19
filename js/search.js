jQuery(function() {
  jQuery('a[href="#search"]').on("click", function(event) {
    event.preventDefault();
    jQuery("#search").addClass("open");
    jQuery('#search > form > input[type="search"]').focus();
  });

  jQuery("#search, #search button.close").on("click keyup", function(event) {
    if (
      event.target == this ||
      event.target.className == "close" ||
      event.keyCode == 27
    ) {
      jQuery(this).removeClass("open");
    }
  });

  jQuery("form").submit(function(event) {
    event.preventDefault();
    return false;
  });
});
