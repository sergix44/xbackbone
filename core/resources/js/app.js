import {Alpine, Livewire} from '../../vendor/livewire/livewire/dist/livewire.esm';
import Plyr from 'plyr';
import 'plyr/dist/plyr.css';
import WaveSurfer from 'wavesurfer.js';
import hljs from 'highlight.js/lib/common';
import {Passkeys, UserCancelledError} from '@laravel/passkeys';

function clipboard(subject) {
    return new Promise(function (resolve, reject) {
        let success = false;

        function listener(e) {
            e.clipboardData.setData("text/plain", subject);
            e.preventDefault();
            success = true;
        }

        document.addEventListener("copy", listener);
        document.execCommand("copy");
        document.removeEventListener("copy", listener);
        success ? resolve() : reject();
    });
};

Alpine.magic('clipboard', () => async subject => {
    await clipboard(subject)
    Livewire.dispatch('clipboard:copied', {text: subject})
})

/**
 * Keeps the "below the fold" info card the same width as the media shown
 * "above the fold". A single ResizeObserver on the media element is the only
 * source of truth: it reports the media's rendered width for every resource
 * type (image, video, pdf, text, audio) and, for replaced elements that expose
 * an intrinsic size (e.g. <img>), the natural dimensions used by the metadata.
 */
Alpine.data('aboveBelowFoldSync', () => ({
    naturalWidth: null,
    naturalHeight: null,
    observer: null,
    init() {
        const media = this.$refs.media;
        const card = this.$refs.card;
        if (!media) {
            return;
        }

        // Intrinsic size from replaced elements (e.g. <img>).
        if (media.tagName === 'IMG') {
            const capture = () => {
                this.naturalWidth = media.naturalWidth;
                this.naturalHeight = media.naturalHeight;
            };
            if (media.complete) {
                capture();
            } else {
                media.addEventListener('load', capture, { once: true });
            }
        }

        // Intrinsic size reported by the video player (see plyrPlayer).
        this.$root.addEventListener('video:meta', (e) => {
            this.naturalWidth = e.detail.width;
            this.naturalHeight = e.detail.height;
        });

        // Mirror the media's rendered width onto the info card below the fold.
        this.observer = new ResizeObserver((entries) => {
            const width = Math.round(entries[0].contentRect.width);
            card.style.maxWidth = `${width}px`;
        });
        this.observer.observe(media);
    },
    destroy() {
        this.observer?.disconnect();
    },
}));

Alpine.data('plyrPlayer', () => ({
    player: null,
    init() {
        const video = this.$refs.video;
        if (!video) {
            return;
        }
        video.addEventListener('loadedmetadata', () => {
            this.$dispatch('video:meta', {
                width: video.videoWidth,
                height: video.videoHeight,
            });
        });
        this.player = new Plyr(video, { resetOnEnd: true });
    },
    destroy() {
        this.player?.destroy();
    },
}));

Alpine.data('wavesurferPlayer', (src) => ({
    ws: null,
    playing: false,
    loading: true,
    volume: 1,
    currentTime: '0:00',
    duration: '0:00',
    init() {
        this.ws = WaveSurfer.create({
            container: this.$refs.waveform,
            waveColor: 'color-mix(in oklch, currentColor 25%, transparent)',
            progressColor: 'currentColor',
            url: src,
            height: 128,
            barWidth: 3,
            barGap: 1,
            barRadius: 3,
        });
        this.ws.on('ready', (d) => {
            this.loading = false;
            this.duration = this.fmt(d);
            this.ws?.play();
        });
        this.ws.on('play', () => { this.playing = true; });
        this.ws.on('pause', () => { this.playing = false; });
        this.ws.on('timeupdate', (t) => { this.currentTime = this.fmt(t); });
        this.ws.on('finish', () => { this.playing = false; });
        this.$watch('volume', (v) => this.ws?.setVolume(v));
    },
    toggle() {
        if (!this.loading) {
            this.ws?.playPause();
        }
    },
    toggleMute() {
        this.volume = this.volume > 0 ? 0 : 1;
    },
    fmt(s) {
        return `${Math.floor(s / 60)}:${String(Math.floor(s % 60)).padStart(2, '0')}`;
    },
    destroy() {
        this.ws?.destroy();
    },
}));

Alpine.data('codeHighlighter', (language = null) => ({
    init() {
        const code = this.$refs.code;
        if (!code) {
            return;
        }
        const lang = language && hljs.getLanguage(language) ? language : null;
        const { value } = lang
            ? hljs.highlight(code.textContent, { language: lang })
            : hljs.highlightAuto(code.textContent);
        code.innerHTML = value;
        code.classList.add('hljs');
    },
}));

/**
 * Drives a gallery card whose preview is still being generated in the background
 * (the server rendered the card because preview_type === FUTURE). It polls the
 * thumbnail endpoint and reacts to the HTTP status, which encodes the state:
 *
 *   200  the preview is ready and the response body *is* the image, so we show
 *        it straight from the downloaded bytes — no second request, no cache or
 *        stale-`src` pitfalls.
 *   425  (Too Early) still being generated; retry after a short delay.
 *   404  the job resolved to "no preview" (e.g. ffmpeg missing); give up.
 *   else a transient network/server hiccup; retry.
 *
 * The 425 is only returned to an explicit `probe` request — a plain thumbnail
 * load (e.g. a bare <img>) just 404s until the preview exists — so we always
 * poll with that flag set.
 *
 * Polling pauses while the tab is hidden and is capped so a stuck job never polls
 * forever. `settled` flips once we reach a terminal state (shown or given up),
 * which lets the placeholder stop pulsing.
 */
const PENDING_PREVIEW_MAX_ATTEMPTS = 40;
const PENDING_PREVIEW_INTERVAL = 3000;

Alpine.data('pendingPreview', (url) => ({
    src: null,
    ready: false,
    settled: false,
    timer: null,
    probeUrl: url + (url.includes('?') ? '&' : '?') + 'probe=1',
    init() {
        this.poll(0);
    },
    async poll(attempt) {
        if (attempt >= PENDING_PREVIEW_MAX_ATTEMPTS) {
            this.settled = true;
            return;
        }
        // Pause without spending an attempt while the tab is in the background.
        if (document.hidden) {
            this.retry(attempt);
            return;
        }

        let response;
        try {
            response = await fetch(this.probeUrl, { cache: 'no-store' });
        } catch {
            this.retry(attempt + 1);
            return;
        }

        if (response.ok) {
            this.src = URL.createObjectURL(await response.blob());
            this.ready = true;
            this.settled = true;
        } else if (response.status === 425) {
            this.retry(attempt + 1);
        } else {
            // 404 (or any other definitive status): no preview is ever coming.
            this.settled = true;
        }
    },
    retry(attempt) {
        this.timer = setTimeout(() => this.poll(attempt), PENDING_PREVIEW_INTERVAL);
    },
    destroy() {
        clearTimeout(this.timer);
        if (this.src) {
            URL.revokeObjectURL(this.src);
        }
    },
}));

/**
 * Drives the "Sign in with a passkey" button on the login page. Runs the
 * WebAuthn verification ceremony against Fortify's guest passkey endpoints and,
 * on success, follows the server-provided redirect. A user dismissing the
 * native prompt (UserCancelledError) is a silent no-op, not an error.
 */
Alpine.data('passkeyLogin', () => ({
    busy: false,
    error: null,
    supported: true,
    init() {
        this.supported = Passkeys.isSupported();
    },
    async login() {
        if (this.busy) {
            return;
        }
        this.busy = true;
        this.error = null;
        try {
            const {redirect} = await Passkeys.verify();
            window.location.assign(redirect ?? '/');
        } catch (e) {
            this.busy = false;
            if (e instanceof UserCancelledError) {
                return;
            }
            this.error = e?.message || 'Passkey sign-in failed.';
        }
    },
}));

/**
 * Drives the "Add a passkey" form in the profile's Passkeys tab. Runs the
 * WebAuthn registration ceremony against Fortify's authenticated endpoints and,
 * on success, asks the Livewire component to refresh its list via an event.
 */
Alpine.data('passkeyManager', () => ({
    name: '',
    busy: false,
    error: null,
    supported: true,
    init() {
        this.supported = Passkeys.isSupported();
    },
    async register() {
        const name = this.name.trim();
        if (this.busy || name === '') {
            return;
        }
        this.busy = true;
        this.error = null;
        try {
            await Passkeys.register({name});
            this.name = '';
            Livewire.dispatch('passkey-registered');
        } catch (e) {
            if (!(e instanceof UserCancelledError)) {
                this.error = e?.message || 'Could not register passkey.';
            }
        } finally {
            this.busy = false;
        }
    },
}));

Livewire.start()
