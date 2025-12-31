/**
 * Offline Map Solution for Project Management
 * Provides basic map functionality without external API dependencies
 */

class OfflineMap {
    constructor() {
        this.indianCities = [
            { name: 'Mumbai', lat: 19.0760, lng: 72.8777, state: 'Maharashtra' },
            { name: 'Delhi', lat: 28.7041, lng: 77.1025, state: 'Delhi' },
            { name: 'Bangalore', lat: 12.9716, lng: 77.5946, state: 'Karnataka' },
            { name: 'Chennai', lat: 13.0827, lng: 80.2707, state: 'Tamil Nadu' },
            { name: 'Kolkata', lat: 22.5726, lng: 88.3639, state: 'West Bengal' },
            { name: 'Hyderabad', lat: 17.3850, lng: 78.4867, state: 'Telangana' },
            { name: 'Pune', lat: 18.5204, lng: 73.8567, state: 'Maharashtra' },
            { name: 'Ahmedabad', lat: 23.0225, lng: 72.5714, state: 'Gujarat' },
            { name: 'Jaipur', lat: 26.9124, lng: 75.7873, state: 'Rajasthan' },
            { name: 'Surat', lat: 21.1702, lng: 72.8311, state: 'Gujarat' },
            { name: 'Lucknow', lat: 26.8467, lng: 80.9462, state: 'Uttar Pradesh' },
            { name: 'Kanpur', lat: 26.4499, lng: 80.3319, state: 'Uttar Pradesh' },
            { name: 'Nagpur', lat: 21.1458, lng: 79.0882, state: 'Maharashtra' },
            { name: 'Indore', lat: 22.7196, lng: 75.8577, state: 'Madhya Pradesh' },
            { name: 'Thane', lat: 19.2183, lng: 72.9781, state: 'Maharashtra' },
            { name: 'Bhopal', lat: 23.2599, lng: 77.4126, state: 'Madhya Pradesh' },
            { name: 'Visakhapatnam', lat: 17.6868, lng: 83.2185, state: 'Andhra Pradesh' },
            { name: 'Pimpri-Chinchwad', lat: 18.6298, lng: 73.7997, state: 'Maharashtra' },
            { name: 'Patna', lat: 25.5941, lng: 85.1376, state: 'Bihar' },
            { name: 'Vadodara', lat: 22.3072, lng: 73.1812, state: 'Gujarat' },
            { name: 'Ghaziabad', lat: 28.6692, lng: 77.4538, state: 'Uttar Pradesh' },
            { name: 'Ludhiana', lat: 30.9010, lng: 75.8573, state: 'Punjab' },
            { name: 'Agra', lat: 27.1767, lng: 78.0081, state: 'Uttar Pradesh' },
            { name: 'Nashik', lat: 19.9975, lng: 73.7898, state: 'Maharashtra' },
            { name: 'Faridabad', lat: 28.4089, lng: 77.3178, state: 'Haryana' },
            { name: 'Meerut', lat: 28.9845, lng: 77.7064, state: 'Uttar Pradesh' },
            { name: 'Rajkot', lat: 22.3039, lng: 70.8022, state: 'Gujarat' },
            { name: 'Kalyan-Dombivli', lat: 19.2403, lng: 73.1305, state: 'Maharashtra' },
            { name: 'Vasai-Virar', lat: 19.4912, lng: 72.8054, state: 'Maharashtra' },
            { name: 'Varanasi', lat: 25.3176, lng: 82.9739, state: 'Uttar Pradesh' }
        ];
    }

    searchCities(query) {
        if (!query || query.length < 2) return [];
        
        const searchTerm = query.toLowerCase();
        return this.indianCities
            .filter(city => 
                city.name.toLowerCase().includes(searchTerm) ||
                city.state.toLowerCase().includes(searchTerm)
            )
            .slice(0, 10)
            .map(city => ({
                name: city.name,
                displayName: `${city.name}, ${city.state}`,
                lat: city.lat,
                lng: city.lng,
                type: 'city'
            }));
    }

    validateCoordinates(lat, lng) {
        const latitude = parseFloat(lat);
        const longitude = parseFloat(lng);

        if (isNaN(latitude) || isNaN(longitude)) {
            return { valid: false, error: 'Coordinates must be numbers' };
        }

        if (latitude < -90 || latitude > 90) {
            return { valid: false, error: 'Latitude must be between -90 and 90' };
        }

        if (longitude < -180 || longitude > 180) {
            return { valid: false, error: 'Longitude must be between -180 and 180' };
        }

        return { valid: true, lat: latitude, lng: longitude };
    }

    findNearestCity(lat, lng) {
        let nearest = null;
        let minDistance = Infinity;

        this.indianCities.forEach(city => {
            const distance = this.calculateDistance(lat, lng, city.lat, city.lng);
            if (distance < minDistance) {
                minDistance = distance;
                nearest = city;
            }
        });

        return nearest ? {
            name: nearest.name,
            displayName: `${nearest.name}, ${nearest.state}`,
            distance: Math.round(minDistance / 1000) // km
        } : null;
    }

    calculateDistance(lat1, lng1, lat2, lng2) {
        const R = 6371e3; // Earth's radius in meters
        const φ1 = lat1 * Math.PI / 180;
        const φ2 = lat2 * Math.PI / 180;
        const Δφ = (lat2 - lat1) * Math.PI / 180;
        const Δλ = (lng2 - lng1) * Math.PI / 180;

        const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
                  Math.cos(φ1) * Math.cos(φ2) *
                  Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

        return R * c; // Distance in meters
    }

    formatCoordinates(lat, lng, precision = 6) {
        return {
            lat: parseFloat(lat).toFixed(precision),
            lng: parseFloat(lng).toFixed(precision),
            display: `${parseFloat(lat).toFixed(4)}, ${parseFloat(lng).toFixed(4)}`
        };
    }

    isLocationInIndia(lat, lng) {
        const indiaBounds = {
            north: 37.6,
            south: 6.4,
            east: 97.25,
            west: 68.7
        };

        return lat >= indiaBounds.south && lat <= indiaBounds.north &&
               lng >= indiaBounds.west && lng <= indiaBounds.east;
    }
}

// Create global instance
window.OfflineMap = new OfflineMap();
