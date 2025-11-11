@extends('layouts.admin.app')

@section('title', 'Update Branch')

@push('css_or_js')
@endpush
@section('content')

    <div class="container py-4">
        <h3>Edit Delivery Zone</h3>

        <form action="{{ route('admin.zone.update', $zone->id) }}" method="POST">
            @csrf
            <div class="container">
                <div class="row">
                    <div class="col-md-12">
                        <input type="text" class="form-control" name="circle_coordinates" id="circle_coordinates">

                        <div class="mb-3">
                            <label>Name</label>
                            <input type="text" name="name" value="{{ old('name', $zone->name) }}"
                                class="form-control">
                        </div>

                        <div class="mb-3">
                            <label>Contact</label>
                            <input type="text" name="contact" value="{{ old('contact', $zone->contact) }}"
                                class="form-control">
                        </div>





                    </div>
                    <div class="col-md-12">
                        {{-- include the circle map partial --}}
                        <div class="mb-3">
                            <div class="form-group">
                                <label>Search Location</label>
                                <input id="searchInput" class="form-control mb-2" type="text"
                                    placeholder="Search location...">
                            </div>
                            <div class="form-group">
                                <label>Radius (meters)</label>
                                <input id="radiusInput" name="radius" class="form-control" type="number"
                                    value="{{ $zone->radius ?? 1000 }}">
                            </div>
                            <input type="hidden" id="latitude" name="latitude" value="{{ $zone->center_lat ?? '' }}">
                            <input type="hidden" id="longitude" name="longitude" value="{{ $zone->center_lng ?? '' }}">

                        </div>

                        <div id="map" style="height: 500px; width: 100%; border-radius: 10px; overflow: hidden;">
                        </div>




                    </div>
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary mt-3">Update Zone</button>
                    </div>
                </div>
            </div>


        </form>
    </div>
    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ \App\Models\BusinessSetting::where('key', 'map_api_key')->value('value') }}&libraries=places">
    </script>

    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ \App\Models\BusinessSetting::where('key', 'map_api_key')->value('value') }}&libraries=places">
    </script>

    <script>
        let map, marker, circle;

        function initMap() {
            const center = {
                lat: parseFloat("{{ $zone->center_lat ?? 28.6139 }}"),
                lng: parseFloat("{{ $zone->center_lng ?? 77.209 }}")
            };

            const mapEl = document.getElementById("map");
            if (!mapEl) {
                console.error("Map container not found.");
                return;
            }

            const searchInput = document.getElementById("searchInput");
            const radiusInput = document.getElementById("radiusInput");

            // Initialize map
            map = new google.maps.Map(mapEl, {
                center: center,
                zoom: 13
            });

            // Place search autocomplete
            const autocomplete = new google.maps.places.Autocomplete(searchInput);
            autocomplete.bindTo("bounds", map);

            // Initial circle
            const radius = parseFloat(radiusInput.value) || 1000;
            circle = new google.maps.Circle({
                map,
                center,
                radius,
                fillColor: "#007bff",
                fillOpacity: 0.3,
                strokeColor: "#0056b3",
                strokeWeight: 2,
                editable: true,
                draggable: true,
            });

            // Marker
            marker = new google.maps.Marker({
                position: center,
                map,
                draggable: true,
            });

            // 🔹 Update inputs initially
            updateFormFields(center.lat, center.lng, radius);

            // 🔹 Search location
            autocomplete.addListener("place_changed", () => {
                const place = autocomplete.getPlace();
                if (!place.geometry) return alert("No location found!");
                const loc = place.geometry.location;
                map.setCenter(loc);
                marker.setPosition(loc);
                circle.setCenter(loc);
                updateFormFields(loc.lat(), loc.lng(), circle.getRadius());
            });

            // 🔹 Drag marker
            marker.addListener("dragend", e => {
                circle.setCenter(e.latLng);
                updateFormFields(e.latLng.lat(), e.latLng.lng(), circle.getRadius());
            });

            // 🔹 Change circle position
            circle.addListener("center_changed", () => {
                const c = circle.getCenter();
                marker.setPosition(c);
                updateFormFields(c.lat(), c.lng(), circle.getRadius());
            });

            // 🔹 Change circle radius (via map handle)
            circle.addListener("radius_changed", () => {
                const c = circle.getCenter();
                updateFormFields(c.lat(), c.lng(), circle.getRadius());
            });

            // 🔹 Manual radius input
            radiusInput.addEventListener("input", () => {
                const r = parseFloat(radiusInput.value);
                if (!isNaN(r)) {
                    circle.setRadius(r);
                    updateFormFields(circle.getCenter().lat(), circle.getCenter().lng(), r);
                }
            });
        }

        // 🔸 Helper to update hidden fields
        function updateFormFields(lat, lng, radius) {
            const latInput = document.getElementById("latitude");
            const lngInput = document.getElementById("longitude");
            const circleInput = document.getElementById("circle_coordinates");

            if (latInput && lngInput && circleInput) {
                latInput.value = lat;
                lngInput.value = lng;
                circleInput.value = getCirclePolygonString(lat, lng, radius);
                console.log("Updated:", lat, lng, radius);
            } else {
                console.error("Missing hidden inputs!");
            }
        }

        // 🔸 Generate polygon coordinates for circle
        function getCirclePolygonString(lat, lng, radius) {
            const coords = [];
            const earthRadius = 6378137;
            const latRad = lat * Math.PI / 180;
            for (let i = 0; i <= 36; i++) {
                const angle = (i * 10) * Math.PI / 180;
                const latOffset = (radius / earthRadius) * Math.cos(angle);
                const lngOffset = (radius / (earthRadius * Math.cos(latRad))) * Math.sin(angle);
                const latPoint = lat + (latOffset * 180 / Math.PI);
                const lngPoint = lng + (lngOffset * 180 / Math.PI);
                coords.push(`(${latPoint},${lngPoint})`);
            }
            return coords.join(',');
        }

        // 🔸 Wait until Google script fully loads and DOM is ready
        document.addEventListener("DOMContentLoaded", function() {
            if (typeof google !== "undefined" && google.maps) {
                initMap();
            } else {
                console.error("Google Maps not loaded.");
            }
        });
    </script>

@endsection
