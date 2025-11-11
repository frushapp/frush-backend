@extends('layouts.admin.app')

@section('title', 'Add new zone')

@push('css_or_js')
@endpush
@section('content')

    <div class="container py-4" style="max-width: 800px;">
        <div class="row">
            <div class="col-md-6">
                <h3 class="mb-3 text-center">🗺️ Search Location & Draw Circle</h3>
            </div>
            <div class="col-md-6">
                <div class="text-right">
                    <a href="{{ url()->previous() }}" class="btn btn-primary btn-sm">
                        Back
                    </a>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.zone.store_radius') }}" class="mt-4">
            {{-- Search + radius input --}}
            <div class="mb-3">
                <div class="form-group">
                    <label class="input-label" for="exampleFormControlInput1">{{ __('messages.name') }}</label>
                    <input type="text" name="name" class="form-control" placeholder="{{ __('messages.new_zone') }}"
                        value="{{ old('name') }}">
                </div>
                <div class="form-group">
                    <label class="input-label" for="exampleFormControlInput1">Contact</label>
                    <input type="text" name="contact" class="form-control" placeholder="Contact"
                        value="{{ old('contact') }}">
                </div>
                <input id="searchInput" class="form-control mb-2" type="text" placeholder="Search location...">
                <input id="radiusInput" class="form-control" type="number" placeholder="Enter radius in meters"
                    value="1000">
            </div>

            {{-- Map container --}}
            <div id="map" style="height: 500px; width: 100%; border-radius: 10px; overflow: hidden;"></div>




            @csrf
            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">
            <input type="hidden" name="radius" id="radius">
            <input type="text" class="form-control" name="circle_coordinates" id="circle_coordinates">

            <div class="text-center">
                <button type="submit" class="btn btn-primary">💾 Save Circle</button>
            </div>
        </form>

    </div>

    {{-- Google Maps Script --}}
    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ \App\Models\BusinessSetting::where('key', 'map_api_key')->value('value') }}&libraries=places">
    </script>

    <script>
        let map, marker, circle;

        function initMap() {
            const defaultCenter = {
                lat: 28.6139,
                lng: 77.2090
            }; // Default: New Delhi

            map = new google.maps.Map(document.getElementById("map"), {
                center: defaultCenter,
                zoom: 12,
            });

            const input = document.getElementById("searchInput");
            const autocomplete = new google.maps.places.Autocomplete(input);
            autocomplete.bindTo("bounds", map);

            autocomplete.addListener("place_changed", function() {
                const place = autocomplete.getPlace();
                if (!place.geometry) return alert("No details available for input: '" + place.name + "'");

                const location = place.geometry.location;
                map.setCenter(location);
                map.setZoom(13);

                if (marker) marker.setMap(null);
                if (circle) circle.setMap(null);

                marker = new google.maps.Marker({
                    position: location,
                    map: map,
                });

                const radius = parseFloat(document.getElementById("radiusInput").value) || 1000;

                circle = new google.maps.Circle({
                    map: map,
                    center: location,
                    radius: radius,
                    fillColor: "#007bff",
                    fillOpacity: 0.3,
                    strokeColor: "#0056b3",
                    strokeWeight: 2,
                    editable: true,
                    draggable: true,
                });

                updateFormFields(location.lat(), location.lng(), radius);

                google.maps.event.addListener(circle, 'radius_changed', () => {
                    updateFormFields(circle.getCenter().lat(), circle.getCenter().lng(), circle
                        .getRadius());
                });

                google.maps.event.addListener(circle, 'center_changed', () => {
                    updateFormFields(circle.getCenter().lat(), circle.getCenter().lng(), circle
                        .getRadius());
                });
            });

            // Change radius manually via input
            document.getElementById("radiusInput").addEventListener('input', () => {
                const newRadius = parseFloat(document.getElementById("radiusInput").value);
                if (circle && !isNaN(newRadius)) {
                    circle.setRadius(newRadius);
                    updateFormFields(circle.getCenter().lat(), circle.getCenter().lng(), newRadius);
                }
            });
        }

        function updateFormFields(lat, lng, radius) {
            document.getElementById("latitude").value = lat;
            document.getElementById("longitude").value = lng;
            document.getElementById("radius").value = radius;

            // Generate circle perimeter coordinates
            const coordinates = getCircleCoordinates(lat, lng, radius);

            // Convert to polygon-style string "(lat,lng),(lat,lng),..."
            const formattedCoords = coordinates.map(c => `(${c.lat},${c.lng})`).join(',');

            document.getElementById("circle_coordinates").value = formattedCoords;
        }

        // Approximate circle boundary
        function getCircleCoordinates(lat, lng, radius) {
            const coords = [];
            const earthRadius = 6378137; // meters
            const latRad = lat * Math.PI / 180;
            const lngRad = lng * Math.PI / 180;

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

            // Close the polygon loop
            coords.push(coords[0]);

            return coords;
        }

        window.onload = initMap;
    </script>


@endsection
