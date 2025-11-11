<div class="mb-3">
    <div class="form-group">
        <label>Search Location</label>
        <input id="searchInput" class="form-control mb-2" type="text" placeholder="Search location...">
    </div>
    <div class="form-group">
        <label>Radius (meters)</label>
        <input id="radiusInput" name="radius" class="form-control" type="number" value="{{ $zone->radius ?? 1000 }}">
    </div>
</div>

<div id="map" style="height: 500px; width: 100%; border-radius: 10px; overflow: hidden;"></div>

<input type="hidden" name="latitude" value="{{ $zone->center_lat ?? '' }}">
<input type="hidden" name="longitude" value="{{ $zone->center_lng ?? '' }}">
<input type="hidden" id="circle_coordinates" name="circle_coordinates">

<script
    src="https://maps.googleapis.com/maps/api/js?key={{ \App\Models\BusinessSetting::where('key', 'map_api_key')->value('value') }}&libraries=places">
</script>

<script>
    document.addEventListener("DOMContentLoaded", initMap);

    function initMap() {
        const center = {
            lat: parseFloat("{{ $zone->center_lat ?? 28.6139 }}"),
            lng: parseFloat("{{ $zone->center_lng ?? 77.209 }}")
        };

        const mapEl = document.getElementById("map");
        const searchInput = document.getElementById("searchInput");
        const radiusInput = document.getElementById("radiusInput");

        map = new google.maps.Map(mapEl, {
            center: center,
            zoom: 13
        });
        const autocomplete = new google.maps.places.Autocomplete(searchInput);
        autocomplete.bindTo("bounds", map);

        const radius = parseFloat(radiusInput.value) || 1000;

        circle = new google.maps.Circle({
            map: map,
            center: center,
            radius: radius,
            fillColor: "#007bff",
            fillOpacity: 0.3,
            strokeColor: "#0056b3",
            strokeWeight: 2,
            editable: true,
            draggable: true,
        });

        marker = new google.maps.Marker({
            position: center,
            map: map,
            draggable: true,
        });

        // ---- LISTENERS ----
        autocomplete.addListener("place_changed", () => {
            const place = autocomplete.getPlace();
            if (!place.geometry) return alert("No location found!");
            const loc = place.geometry.location;
            map.setCenter(loc);
            marker.setPosition(loc);
            circle.setCenter(loc);
            updateFormFields(loc.lat(), loc.lng(), circle.getRadius());
        });

        marker.addListener("dragend", e => {
            circle.setCenter(e.latLng);
            updateFormFields(e.latLng.lat(), e.latLng.lng(), circle.getRadius());
        });

        circle.addListener("center_changed", () => {
            const c = circle.getCenter();
            marker.setPosition(c);
            updateFormFields(c.lat(), c.lng(), circle.getRadius());
        });

        circle.addListener("radius_changed", () => {
            updateFormFields(circle.getCenter().lat(), circle.getCenter().lng(), circle.getRadius());
        });

        radiusInput.addEventListener("input", () => {
            const r = parseFloat(radiusInput.value);
            if (!isNaN(r)) {
                circle.setRadius(r);
                updateFormFields(circle.getCenter().lat(), circle.getCenter().lng(), r);
            }
        });

        // ---- FORM SUBMIT ----
        const form = document.querySelector("form");
        form.addEventListener("submit", function(e) {
            const c = circle.getCenter();
            const r = circle.getRadius();
            updateFormFields(c.lat(), c.lng(), r);
            if (!document.getElementById("circle_coordinates").value) {
                e.preventDefault();
                alert("Please select a valid map location before submitting.");
            }
        });

        // initial values
        updateFormFields(center.lat, center.lng, radius);
    }

    function updateFormFields(lat, lng, radius) {
        document.getElementById("latitude").value = lat;
        document.getElementById("longitude").value = lng;
        document.getElementById("circle_coordinates").value = getCirclePolygonString(lat, lng, radius);
    }

    function getCirclePolygonString(lat, lng, radius) {
        const coords = getCircleCoordinates(lat, lng, radius);
        return coords.map(c => `(${c.lat},${c.lng})`).join(',');
    }

    function getCircleCoordinates(lat, lng, radius) {
        const coords = [];
        const earthRadius = 6378137;
        const latRad = lat * Math.PI / 180;
        for (let i = 0; i < 36; i++) {
            const angle = (i * 10) * Math.PI / 180;
            const latOffset = (radius / earthRadius) * Math.cos(angle);
            const lngOffset = (radius / (earthRadius * Math.cos(latRad))) * Math.sin(angle);
            const latPoint = lat + (latOffset * 180 / Math.PI);
            const lngPoint = lng + (lngOffset * 180 / Math.PI);
            coords.push({
                lat: latPoint,
                lng: lngPoint
            });
        }
        coords.push(coords[0]);
        return coords;
    }
</script>
