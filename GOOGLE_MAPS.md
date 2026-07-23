# Google Maps setup

Google Maps powers two things in this app, both optional at runtime:

- Address autocomplete / reverse-geocoding when a customer sets their delivery location.
- The live map on the delivery tracking page (`/track-delivery/{id}`) — agent, vendor, and
  customer markers, route line, and live position updates.

Without a key, both features degrade gracefully: location entry falls back to manual address
fields, and tracking falls back to a real-time status timeline with no map. Nothing is faked.

## Required credential

Add one browser-exposed, **HTTP-referrer restricted** API key with these APIs enabled:

- Maps JavaScript API
- Geocoding API

Never use a server-side/unrestricted key here — this key is sent to the browser.

## Configuration

Set in `.env`:

```
GOOGLE_MAPS_API_KEY=your-restricted-browser-key
GOOGLE_MAPS_MAP_ID=          # optional, for a custom Cloud-based map style
GOOGLE_MAPS_REGION=NG
GOOGLE_MAPS_LANGUAGE=en
```

Read via `config('services.google_maps.*')` (`config/services.php`). No code changes are needed
to activate the map once the key is set — every consumer already checks for a configured key and
renders the live map instead of the fallback automatically.
