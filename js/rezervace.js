jQuery(document).ready(function($) {
  var height;
  height =
    jQuery(".content--trip__reservation-form__content form").innerHeight() *
      0.8 +
    "px";
  /// console.log("Outer height of div: " + height);
  jQuery(".content--trip__reservation-form__content form").css(
    "height",
    height
  );
});

jQuery(window).resize(function() {
  var height;
  height =
    jQuery(".content--trip__reservation-form__content form").innerHeight() *
      0.8 +
    "px";
  /// console.log("Outer height of div: " + height);
  jQuery(".content--trip__reservation-form__content form").css(
    "height",
    height
  );
});