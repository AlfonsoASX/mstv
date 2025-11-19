<?php
/**
 * ===============================================
 * PANEL ADMIN → GESTIÓN DE GEOCERCAS
 * Archivo: backend/admin/geocercas.php
 * ===============================================
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

// Obtener sitios registrados
$stmt = $db->query("SELECT id, nombre, ubicacion, lat, lng, radio_metros, poligono_json 
                    FROM sitios ORDER BY nombre");
$sitios = $stmt->fetchAll();
?>

<div class="container">

    <h3 class="mb-4">Geocercas de Sitios</h3>

    <!-- Selección de sitio -->
    <div class="card p-3 shadow-sm mb-4">
        <label><strong>Seleccionar sitio</strong></label>
        <select id="sitioSelect" class="form-select" onchange="cargarGeocerca()">
            <option value="">--Seleccione uno--</option>
            <?php foreach ($sitios as $s): ?>
                <option value='<?= json_encode($s) ?>'>
                    <?= htmlspecialchars($s['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Mapa -->
    <div class="card p-3 mb-4 shadow-sm">
        <h5>Mapa y Geocerca</h5>
        <div id="map" style="height: 450px; width: 100%;"></div>
        <small class="text-muted">Click en el mapa para agregar puntos del polígono.</small>
    </div>

    <!-- Formulario de guardado -->
    <div class="card p-3 shadow-sm mb-5">
        <h5>Guardar Configuración</h5>
        <form id="geoForm" method="POST" action="guardar_geocerca.php">

            <input type="hidden" name="sitio_id" id="sitio_id">
            <input type="hidden" name="lat" id="lat">
            <input type="hidden" name="lng" id="lng">
            <input type="hidden" name="radio_metros" id="radio_metros">
            <input type="hidden" name="poligono_json" id="poligono_json">

            <div class="row mb-3">
                <div class="col-md-4">
                    <label>Latitud</label>
                    <input type="text" class="form-control" id="lat_mostrar" disabled>
                </div>
                <div class="col-md-4">
                    <label>Longitud</label>
                    <input type="text" class="form-control" id="lng_mostrar" disabled>
                </div>
                <div class="col-md-4">
                    <label>Radio (mts)</label>
                    <input type="number" class="form-control" id="radio_mostrar" min="50" max="1000">
                </div>
            </div>

            <button type="submit" class="btn btn-success">Guardar Geocerca</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>


<!-- Google Maps -->
<script src="https://maps.googleapis.com/maps/api/js?key=TU_API_KEY&libraries=drawing"></script>

<script>
let map, marker, circle, polygon;
let selectedSitio = null;
let drawingManager;
let polyCoords = [];

function cargarGeocerca() {
    const data = document.getElementById('sitioSelect').value;
    if (!data) return;

    selectedSitio = JSON.parse(data);

    // Insertar datos en formulario
    document.getElementById('sitio_id').value = selectedSitio.id;
    document.getElementById('lat').value = selectedSitio.lat;
    document.getElementById('lng').value = selectedSitio.lng;
    document.getElementById('lat_mostrar').value = selectedSitio.lat;
    document.getElementById('lng_mostrar').value = selectedSitio.lng;
    document.getElementById('radio_mostrar').value = selectedSitio.radio_metros;
    document.getElementById('radio_metros').value = selectedSitio.radio_metros;

    initMap(selectedSitio.lat, selectedSitio.lng);
}

function initMap(lat = 21.122, lng = -101.682) {
    map = new google.maps.Map(document.getElementById('map'), {
        center: { lat: parseFloat(lat), lng: parseFloat(lng) },
        zoom: 15
    });

    // Marker central
    marker = new google.maps.Marker({
        position: { lat: parseFloat(lat), lng: parseFloat(lng) },
        map: map,
        draggable: true,
        title: "Arrastra para mover centro"
    });

    marker.addListener('dragend', function(e) {
        actualizarCentro(e.latLng.lat(), e.latLng.lng());
    });

    // Dibujar círculo si existe
    circle = new google.maps.Circle({
        map: map,
        center: marker.getPosition(),
        radius: parseFloat(document.getElementById('radio_mostrar').value || 200),
        strokeColor: '#008800',
        strokeOpacity: 0.8,
        strokeWeight: 2,
        fillColor: '#008800',
        fillOpacity: 0.15
    });

    // Si hay polígono guardado, mostrarlo
    if (selectedSitio.poligono_json) {
        polyCoords = JSON.parse(selectedSitio.poligono_json);
        polygon = new google.maps.Polygon({
            paths: polyCoords,
            map: map,
            strokeColor: '#FF0000',
            strokeOpacity: 0.8,
            fillColor: '#FF0000',
            fillOpacity: 0.1
        });
    }

    // Manager para dibujar polígono nuevo
    drawingManager = new google.maps.drawing.DrawingManager({
        drawingMode: null,
        drawingControl: true,
        drawingControlOptions: {
            drawingModes: ['polygon']
        }
    });
    drawingManager.setMap(map);

    google.maps.event.addListener(drawingManager, 'polygoncomplete', function(poly) {
        if (polygon) polygon.setMap(null);
        polygon = poly;
        polyCoords = poly.getPath().getArray().map(coord => ({
            lat: coord.lat(),
            lng: coord.lng()
        }));
        document.getElementById('poligono_json').value = JSON.stringify(polyCoords);
    });
}

function actualizarCentro(lat, lng) {
    document.getElementById('lat').value = lat;
    document.getElementById('lng').value = lng;
    document.getElementById('lat_mostrar').value = lat;
    document.getElementById('lng_mostrar').value = lng;
    circle.setCenter({lat:lat, lng:lng});
}
</script>
