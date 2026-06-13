import {Alpine, Livewire} from '../../vendor/livewire/livewire/dist/livewire.esm';
import Plyr from 'plyr';
import 'plyr/dist/plyr.css';
import WaveSurfer from 'wavesurfer.js';

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

Alpine.data('plyrPlayer', () => ({
    player: null,
    init() {
        this.$refs.video.addEventListener('loadedmetadata', () => {
            this.$dispatch('video:meta', {
                width: this.$refs.video.videoWidth,
                height: this.$refs.video.videoHeight,
            });
        });
        this.player = new Plyr(this.$refs.video, { resetOnEnd: true });
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

Livewire.start()
