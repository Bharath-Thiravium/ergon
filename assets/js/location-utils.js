/**
 * Location Utilities for Project Management
 * Provides location search, geocoding, and map functionality
 */

class LocationUtils {
    constructor() {
        this.geocodeCache = new Map();
        this.searchCache = new Map();
        this.defaultLocation = { lat: 20.5937, lng: 78.9629 }; // India center
    }

    /**
     * Initialize location services
     */
    init() {
        this.setupGeolocationAPI();
        // Preloading disabled to prevent CORS errors
    }

    /**
     * Setup geolocation API with fallbacks
     */
    setupGeolocationAPI() {
        if (!navigator.geolocation) {
            console.warn('Geolocation is not supported by this browser');
            return false;
        }
        return true;
    }

    /**
     * Get current position with enhanced error handling
     */
    getCurrentPosition(options = {}) {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Geolocation not supported'));
                return;
            }

            const defaultOptions = {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 300000 // 5 minutes
            };

            const finalOptions = { ...defaultOptions, ...options };

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    resolve({
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                        accuracy: position.coords.accuracy
                    });
                },
                (error) => {
                    let errorMessage = 'Location access denied';
                    switch (error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage = 'Location access denied by user';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage = 'Location information unavailable';
                            break;
                        case error.TIMEOUT:
                            errorMessage = 'Location request timed out';
                            break;
                    }
                    reject(new Error(errorMessage));
                },
                finalOptions
            );
        });
    }

    /**
     * Search for locations using Nominatim API with caching
     */
    async searchLocations(query, limit = 5) {
        if (!query || query.length < 3) {
            throw new Error('Query must be at least 3 characters long');
        }

        const cacheKey = `${query.toLowerCase()}_${limit}`;
        if (this.searchCache.has(cacheKey)) {
            return this.searchCache.get(cacheKey);
        }

        try {
            const response = await fetch(
                `https://nominatim.openstreetmap.org/search?` +
                `format=json&q=${encodeURIComponent(query)}&limit=${limit}&addressdetails=1&countrycodes=in`,
                {
                    headers: {
                        'User-Agent': 'ErgonSite/1.0'
                    }
                }
            );

            if (!response.ok) {
                throw new Error(`Search failed: ${response.status}`);
            }

            const data = await response.json();
            const results = data.map(item => ({
                lat: parseFloat(item.lat),
                lng: parseFloat(item.lon),
                displayName: item.display_name,
                name: item.name || item.display_name.split(',')[0],
                type: item.type,
                importance: item.importance || 0
            }));

            // Cache results for 10 minutes
            this.searchCache.set(cacheKey, results);
            setTimeout(() => this.searchCache.delete(cacheKey), 600000);

            return results;
        } catch (error) {
            console.error('Location search error:', error);
            throw new Error('Failed to search locations. Please try again.');
        }
    }

    /**
     * Reverse geocode coordinates to get place name
     */
    async reverseGeocode(lat, lng) {
        const cacheKey = `${lat.toFixed(4)}_${lng.toFixed(4)}`;
        if (this.geocodeCache.has(cacheKey)) {
            return this.geocodeCache.get(cacheKey);
        }

        try {
            const response = await fetch(
                `https://nominatim.openstreetmap.org/reverse?` +
                `format=json&lat=${lat}&lon=${lng}&addressdetails=1`,
                {
                    headers: {
                        'User-Agent': 'ErgonSite/1.0'
                    }
                }
            );

            if (!response.ok) {
                throw new Error(`Reverse geocoding failed: ${response.status}`);
            }

            const data = await response.json();
            const result = {
                name: data.name || data.display_name?.split(',')[0] || 'Unknown Location',
                displayName: data.display_name || 'Unknown Location',
                address: data.address || {}
            };

            // Cache result for 1 hour
            this.geocodeCache.set(cacheKey, result);
            setTimeout(() => this.geocodeCache.delete(cacheKey), 3600000);

            return result;
        } catch (error) {
            console.error('Reverse geocoding error:', error);
            return {
                name: `Location (${lat.toFixed(4)}, ${lng.toFixed(4)})`,
                displayName: `Location (${lat.toFixed(4)}, ${lng.toFixed(4)})`,
                address: {}
            };
        }
    }

    /**
     * Validate coordinates
     */
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

    /**
     * Calculate distance between two points (Haversine formula)
     */
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

    /**
     * Format coordinates for display
     */
    formatCoordinates(lat, lng, precision = 6) {
        return {
            lat: parseFloat(lat).toFixed(precision),
            lng: parseFloat(lng).toFixed(precision),
            display: `${parseFloat(lat).toFixed(4)}, ${parseFloat(lng).toFixed(4)}`
        };
    }

    /**
     * Preload common Indian locations for faster search
     */
    preloadCommonLocations() {
        // Disabled to prevent CORS errors on page load
        // Preloading will only happen when user actually searches
        return;
    }

    /**
     * Get location suggestions based on partial input
     */
    getLocationSuggestions(input) {
        const suggestions = [
            'Mumbai, Maharashtra',
            'Delhi, India',
            'Bangalore, Karnataka',
            'Chennai, Tamil Nadu',
            'Kolkata, West Bengal',
            'Hyderabad, Telangana',
            'Pune, Maharashtra',
            'Ahmedabad, Gujarat',
            'Jaipur, Rajasthan',
            'Surat, Gujarat'
        ];

        return suggestions.filter(suggestion =>
            suggestion.toLowerCase().includes(input.toLowerCase())
        ).slice(0, 5);
    }

    /**
     * Check if location is within India (approximate bounds)
     */
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

    /**
     * Get timezone for coordinates
     */
    async getTimezone(lat, lng) {
        try {
            // For Indian locations, return IST
            if (this.isLocationInIndia(lat, lng)) {
                return 'Asia/Kolkata';
            }
            
            // For other locations, you might want to use a timezone API
            return 'UTC';
        } catch (error) {
            console.error('Timezone detection error:', error);
            return 'Asia/Kolkata'; // Default to IST
        }
    }
}

// Create global instance
window.LocationUtils = new LocationUtils();

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.LocationUtils.init();
    });
} else {
    window.LocationUtils.init();
}
