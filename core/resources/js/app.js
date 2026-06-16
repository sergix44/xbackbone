import {Alpine, Livewire} from '../../vendor/livewire/livewire/dist/livewire.esm';
import Plyr from 'plyr';
import 'plyr/dist/plyr.css';
import WaveSurfer from 'wavesurfer.js';
import hljs from 'highlight.js/lib/common';

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
        // This component sits on the Livewire root element, where Alpine's
        // $refs do not reliably resolve nested descendants, so the media and
        // card elements are queried directly from this subtree instead.
        const media = this.$el.querySelector('[x-ref="media"]');
        const card = this.$el.querySelector('[x-ref="card"]');
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
            if (card) {
                card.style.maxWidth = `${width}px`;
            }
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

Livewire.start()
