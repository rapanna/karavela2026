jQuery(function($) {
    $.fn.postRouteForm = function() {
        var formTable = this;

        formTable.settings = {
            datepicker: {
                dateFormat: "yy-mm-dd",
                autoSize: true,
                changeYear: true
            },
            map: {
                center: new google.maps.LatLng(0, 0),
                zoom: 1,
                mapTypeId: google.maps.MapTypeId.TERRAIN,
                scrollwheel: false
            },
            route: {
                strokeColor: "#FF0000",
                strokeOpacity: 1.0,
                strokeWeight: 3
            }
        };

        init();

        var marker;

        function init() {
            if ($("#map-gps-route").length) {
                formTable.settings.route.map = new google.maps.Map(
                    document.getElementById("map-gps-route"),
                    formTable.settings.map
                );
                formTable.route = new google.maps.Polyline(formTable.settings.route);

                // marker = new GMarker(center, {icon: new GIcon(G_DEFAULT_ICON, "bigmarker.png"), draggable: true});
                // marker = new GMarker(center, {draggable: true});

                var last_lat = $("tr.gps-loc")
                    .last()
                    .find("input.latitudes")
                    .val();
                var last_lot = $("tr.gps-loc")
                    .last()
                    .find("input.longitudes")
                    .val();

                //console.log("test:" + last_lat);

                if (last_lat == null || last_lot == null) {
                    marker = new google.maps.Marker({
                        map: formTable.settings.route.map,
                        position: new google.maps.LatLng(
                            parseFloat(40.004003),
                            parseFloat(-83.019363)
                        )
                    });
                } else {
                    marker = new google.maps.Marker({
                        map: formTable.settings.route.map,
                        position: new google.maps.LatLng(
                            parseFloat(last_lat),
                            parseFloat(last_lot)
                        )
                    });
                }
                marker.setDraggable(true);

                google.maps.event.addListener(marker, "dragend", function(event) {
                    placeMarker(event.latLng, "Click Generated Marker", marker);
                });

                updateRoute();
            }
        }

        function placeMarker(location) {
            if (marker == undefined) {
                marker = new google.maps.Marker({
                    map: formTable.settings.route.map,
                    position: new google.maps.LatLng(
                        parseFloat(40.004003),
                        parseFloat(-83.019363)
                    )
                });
            } else {
                marker.setPosition(location);
                //console.log(location);
                document.getElementById("lat_p").value = marker.getPosition().lat();
                document.getElementById("lot_p").value = marker.getPosition().lng();
            }
            formTable.settings.route.map.setCenter(location);
        }

        /*
			// A function to create the marker and set up the event window
		function centerMarker() {
			marker.setPoint(new GLatLng(map.getCenter().lat(), map.getCenter().lng()))
		  	document.getElementById('lat_p').value = marker.getPoint().lat();
		  	document.getElementById('lot_p').value = marker.getPoint().lng();
		}
*/
        /*
    	var map = new GMap2(document.getElementById("map"));



			GEvent.addListener(marker, "dragend", function() {
			  document.getElementsByClassName('latitudes').value = marker.getPoint().lat();
			  document.getElementsByClassName('longitudes').value = marker.getPoint().lng();
			});*/

        var markersArray = [];

        function clearOverlays() {
            for (var i = 0; i < markersArray.length; i++) {
                markersArray[i].setMap(null);
            }
            markersArray = [];
        }

        function updateRoute() {
            //console.log("update route");
            var position;
            var empty = false;
            var positions = new Array();
            var zoom = new google.maps.LatLngBounds();

            $("tr.gps-loc").each(function(i) {
                if ((position = getPosition(i))) {
                    positions.push(position);
                    //	var marker = new google.maps.Marker({map: formTable.settings.route.map, position: getPosition(i)});
                    //	marker.setDraggable (true);
                    //	markersArray.push(marker);

                    zoom.extend(position);
                } else {
                    empty = true;
                }
            });
            if (positions.length) formTable.settings.route.map.fitBounds(zoom);
            formTable.route.setPath(positions);
            if (!empty) {
                $(".add-location")
                    .attr("disabled", false)
                    .animate({ opacity: 1 });
            } else {
                $(".add-location")
                    .attr("disabled", true)
                    .animate({ opacity: 0.5 });
            }
            if ($("tr.location").length > 1) {
                $(".delete-location")
                    .attr("disabled", false)
                    .animate({ opacity: 1 });
            } else {
                $(".delete-location")
                    .attr("disabled", true)
                    .animate({ opacity: 0.5 });
            }
        }

        function getPosition(i) {
            var latitude, longitude;
            latitude = $("input.latitudes")
                .eq(i)
                .val();
            //console.log(latitude);
            longitude = $("input.longitudes")
                .eq(i)
                .val();
            if (!latitude || !longitude) return null;
            //centerMarker(latitude, longitude);
            return new google.maps.LatLng(
                parseFloat(latitude),
                parseFloat(longitude)
            );
        }

        $("#add-new-gps-refresh").on("click", function(e) {
            updateRoute();
        });

        $('table#routes-table').on('click', '.odebrat_gps', function () {
          $(this)
            .parents('tr.gps-loc')
            .animate({ backgroundColor: '#dc143c' }, 'fast')
            .hide('fast', function () {
              //$(this).data('marker').setMap(null);
              $(this).remove();
              //formTable.updateRoute();
            });

          var last_lat = $('tr.gps-loc')
            .last()
            .prev('tr.gps-loc')
            .find('input.latitudes')
            .val();
          var last_lot = $('tr.gps-loc')
            .last()
            .prev('tr.gps-loc')
            .find('input.longitudes')
            .val();
          var newLatLng = new google.maps.LatLng(last_lat, last_lot);
          //console.log(newLatLng);
          marker.setPosition(newLatLng);

          updateRoute();
        });

        $("#add-new-gps").on("click", function(e) {
            var gps_info_lat_p = $("input[name=gps_info_lat_p]").val();
            var gps_info_lot_p = $("input[name=gps_info_lot_p]").val();

            if(!$('table#routes-table').attr('data-rows')){
             var newRowNumber = $('table#routes-table tr:last').index() + 1;
            } else {
                var newRowNumber =
                  parseInt($('table#routes-table').attr('data-rows')) + 1;
            }

            $('table#routes-table').attr('data-rows', newRowNumber);

            if (newRowNumber == 0){
                newRowNumber = 1;
            }

              var row = $(
                '<tr class="gps-loc"><td>' +
                  newRowNumber +
                  '</td><td><input class="city_names" type="text" name="gps_info_name[]" /></td><td><input class="latitudes" value="' +
                  gps_info_lat_p +
                  '" type="text" name="gps_info_lat[]" /></td><td><input value="' +
                  gps_info_lot_p +
                  '" class="longitudes" type="text" name="gps_info_lot[]" /></td><td><input type="button" value="Odebrat" class="odebrat_gps"/></td></tr>'
              )
                .hide()
                .appendTo($('#route-loc-gps'));
            row.show("fast").css("display", "table-row");
        });

        var delete_gps_loc = function() {
            $(this)
                .parents("tr.gps-loc")
                .animate({ backgroundColor: "#dc143c" }, "fast")
                .hide("fast", function() {
                    $(this)
                        .data("marker")
                        .setMap(null);
                    $(this).remove();
                    formTable.updateRoute();
                });
        };
    };

    $(document).ready(function() {
        $("#post-travel-route").postRouteForm();
    });
});

////////// Přidávání termínu zájedu
jQuery(function($) {
    jQuery("#pridat-termin-zajezdu").on("click", function(e) {
        var kod = $("input[name=tm_kod]").val();
        var tm_od = $("input[name=tm_od]").val();
        var tm_do = $("input[name=tm_do]").val();
        var tm_cena_celk = $("input[name=tm_cena_celk]").val();
        var tm_cena_fm = $("input[name=tm_cena_fm]").val();
        var tm_cena_lm = $("input[name=tm_cena_lm]").val();
        var tm_info = $("input[name=tm_info]").val();

        var termin = $(
                '<tr class="termin-zaj"><td><input type="text" name="tm_kod_p[]" value="' +
                kod +
                '"> </td><td><input type="text" name="tm_od_p[]" class="datePicker" value="' +
                tm_od +
                '"></td> <td><input type="text" name="tm_do_p[]" class="datePicker" value="' +
                tm_do +
                '"></td> <td><input type="text" name="tm_cena_celk_p[]" value="' +
                tm_cena_celk +
                '"></td> <td><input type="text" name="tm_cena_fm_p[]" value="' +
                tm_cena_fm +
                '"></td> <td><input type="text" name="tm_cena_lm_p[]" value="' +
                tm_cena_lm +
                '"></td> <td><input type="text" name="tm_info_p[]" value="' +
                tm_info +
                '"></td><td><select style="width: 200px" name="tm_homepage_p[]"><option value="Ne"> Ne </option><option value="Ano"> Ano </option></select></td><td><input type="button" class="odebrat-termin-zajezdu" value="Odebrat"></td></tr>'
            )
            .hide()
            .appendTo($("#terminy-zajezdu"));
        termin.show("fast").css("display", "table-row");
    });

    //Odebere zájezd
    jQuery('#terminy-zajezdu-table').on(
      'click',
      '.odebrat-termin-zajezdu',
      function () {
        jQuery(this)
          .parents('tr.termin-zaj')
          .animate({ backgroundColor: '#dc143c' }, 'fast')
          .hide('fast', function () {
            jQuery(this).remove();
          });
      }
    );
});
jQuery(document).ready(function($) {
    $.datepicker.regional["cs"] = {
        closeText: "Cerrar",
        prevText: "Předchozí",
        nextText: "Další",
        currentText: "Hoy",
        monthNames: [
            "Leden",
            "Únor",
            "Březen",
            "Duben",
            "Květen",
            "Červen",
            "Červenec",
            "Srpen",
            "Září",
            "Říjen",
            "Listopad",
            "Prosinec"
        ],
        monthNamesShort: [
            "Leden",
            "Únor",
            "Březen",
            "Duben",
            "Květen",
            "Červen",
            "Červenec",
            "Srpen",
            "Září",
            "Říjen",
            "Listopad",
            "Prosinec"
        ],
        dayNames: [
            "Neděle",
            "Pondělí",
            "Úterý",
            "Středa",
            "Čtvrtek",
            "Pátek",
            "Sobota"
        ],
        dayNamesShort: ["Ne", "Po", "Út", "St", "Čt", "Pá", "So"],
        dayNamesMin: ["Ne", "Po", "Út", "St", "Čt", "Pá", "So"],
        weekHeader: "Sm",
        dateFormat: "dd.mm.yy",
        firstDay: 1,
        isRTL: false,
        showMonthAfterYear: false,
        yearSuffix: ""
    };

    $.datepicker.setDefaults($.datepicker.regional["cs"]);

    $(".datePicker").datepicker({
        dateFormat: "yy-mm-dd"
    });

    $("#datepicker_zajezdy").datepicker({
        changeMonth: true,
        changeYear: true
    });

    // jQuery UI Dialog

    $("#dialog-confirm").dialog({
        autoOpen: false,
        width: 400,
        modal: true,
        resizable: false,
        buttons: {
            "Odeslat poptávku": function() {
                $("form#contactForm li.buttons button").fadeOut("normal", function() {
                    $(this)
                        .parent()
                        .append(
                            '<img src="/wp-content/themes/td-v3/images/template/loading.gif" alt="Loading…" height="31" width="31" />'
                        );
                });

                var formInput = $("form#contactForm").serialize();
                $.post($("form#contactForm").attr("action"), formInput, function(data) {
                    $("form#contactForm").slideUp("fast", function() {
                        $(this).before(
                            '<p class="thanks"><strong>Děkujeme!</strong> Vaše rezeravace byla úspěšně odeslána.</p><iframe width="119" height="22" frameborder="0" scrolling="no" src="https://c.imedia.cz/checkConversion?c=100010225&color=ffffff&v=' +
                            cenaiframe +
                            '"></iframe>'
                        );
                    });
                });

                $(this).dialog("close");
            },
            Zavřít: function() {
                $(this).dialog("close");
            }
        }
    });

    $("form#contactForm").submit(function(e) {
        e.preventDefault();

        $("form#contactForm .error").remove();
        var hasError = false;
        $(".requiredField").each(function() {
            if (jQuery.trim($(this).val()) == "") {
                var labelText = $(this)
                    .closest("td label")
                    .text();
                $(this)
                    .parent()
                    .append('<span class="error">Musíte vyplnit toto pole !.</span>');
                hasError = true;
            } else if ($(this).hasClass("email")) {
                if (!isValidEmailAddress($(this).val())) {
                    hasError = true;
                    $(this)
                        .parent()
                        .append('<span class="error">Nesprávný formát emailu.</span>');
                }
            }
        });

        var stringva = "";
        $(".ans:checked").each(function() {
            var valuess = $(this).val();
            stringva += valuess;
        });

        $("p.s_nezevZaj").html($("input#nazevZaj").val());
        $("p.s_terminZaj").html($("input#terminZaj").val());
        $("p.s_cenaZaj").html($("input#cenaZaj").val());
        $("p.s_jmeno").html($("input#f_jmeno").val());
        $("p.s_prijmeni").html($("input#f_prijmeni").val());
        $("p.s_tel").html($("input#f_telefon").val());
        $("p.s_email").html($("input#f_email").val());
        $("p.s_adresa").html($("textarea#f_adresa").val());
        $("p.s_sleva").html(stringva);
        $("p.s_nastup").html($("input#f_nastup").val());
        $("p.s_dalsi").html($("textarea#f_dalsi").val());
        $("p.s_pozn").html($("textarea#f_pozn").val());

        if (!hasError) {
            $("#dialog-confirm").data("width.dialog", 400);
            $("#dialog-confirm").dialog("open");
        }

        return false;
    });

    function isValidEmailAddress(emailAddress) {
        var pattern = new RegExp(
            /^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i
        );
        return pattern.test(emailAddress);
    }
});