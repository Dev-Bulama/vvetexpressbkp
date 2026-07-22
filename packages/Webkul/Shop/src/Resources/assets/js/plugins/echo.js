/**
 * Real-time delivery-agent location updates and delivery-status changes are
 * pushed over Laravel Reverb (Pusher-protocol compatible). If no Reverb key
 * is configured (VITE_REVERB_APP_KEY empty), window.Echo stays undefined -
 * callers must check for it and fall back to the status timeline instead of
 * assuming live updates are always available.
 */
import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

if (import.meta.env.VITE_REVERB_APP_KEY) {
    window.Echo = new Echo({
        broadcaster: "reverb",
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? "https") === "https",
        enabledTransports: ["ws", "wss"],
        // Private/presence channel auth reuses window.axios (set up in
        // plugins/axios.js) instead of pusher-js's built-in XHR authorizer,
        // so it picks up the same XSRF-TOKEN cookie -> X-XSRF-TOKEN header
        // handling every other POST in this app already relies on. Without
        // this, /broadcasting/auth 403s with no CSRF token attached.
        authorizer: (channel) => ({
            authorize: (socketId, callback) => {
                window.axios
                    .post("/broadcasting/auth", {
                        socket_id: socketId,
                        channel_name: channel.name,
                    })
                    .then((response) => callback(false, response.data))
                    .catch((error) => callback(true, error));
            },
        }),
    });
}

export default {
    install(app) {
        app.config.globalProperties.$echo = window.Echo;
    },
};
