// Detail zájezdu – GPS mapa (Leaflet) + galerie (slick + GLightbox).
// Data se čtou z atributů #map-gps-route (data-points, data-zoom).

document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('map-gps-route');
    if (!el || typeof L === 'undefined') return;

    var pts        = JSON.parse(el.dataset.points || '[]');
    var manualZoom = parseInt(el.dataset.zoom, 10); // NaN = automaticky
    var map        = L.map(el, { scrollWheelZoom: false });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 18
    }).addTo(map);

    var latlngs = [];
    pts.forEach(function (p) {
        if (!p || p[0] == null || p[1] == null) return;
        var lat = parseFloat(p[0]), lng = parseFloat(p[1]), name = p[2] || '';
        latlngs.push([lat, lng]);
        var m = L.marker([lat, lng]).addTo(map);
        if (name) m.bindPopup(name);
    });

    if (latlngs.length === 1) {
        map.setView(latlngs[0], isNaN(manualZoom) ? 6 : manualZoom);
    } else if (latlngs.length > 1) {
        L.polyline(latlngs, { color: '#e63e23', weight: 3 }).addTo(map);
        var bounds = L.latLngBounds(latlngs);
        if (isNaN(manualZoom)) {
            map.fitBounds(bounds.pad(0.15));
        } else {
            map.setView(bounds.getCenter(), manualZoom);
        }
    }
});

jQuery(function ($) {
    if (!$('.gallery-images').length) return;

    $('.gallery-images').slick({
        dots: false,
        infinite: false,
        adaptiveHeight: false,
        lazyLoad: 'ondemand',
        speed: 300,
        slidesToShow: 3,
        slidesToScroll: 3,
        responsive: [
            { breakpoint: 1024, settings: { slidesToShow: 3, slidesToScroll: 3 } },
            { breakpoint: 600,  settings: { slidesToShow: 2, slidesToScroll: 2 } },
            { breakpoint: 480,  settings: { slidesToShow: 1, slidesToScroll: 1 } }
        ]
    });

    // Lightbox (GLightbox) – po inicializaci slicku, ať se naváže na reálné slidy
    if (typeof GLightbox !== 'undefined') {
        GLightbox({ selector: '.trip--photogallery .glightbox' });
    }
});
