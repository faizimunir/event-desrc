import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode';

const SCANNER_FORMATS = [Html5QrcodeSupportedFormats.QR_CODE];

function scannerDataFactory(regionId, options = {}) {
    return {
        regionId,
        embedded: Boolean(options.embedded),
        scanner: null,
        open: false,
        starting: false,
        processing: false,
        error: null,
        lastScan: '',
        lastScanAt: 0,
        cooldownMs: 2000,

        init() {
            const stop = () => this.closeScanner();
            document.addEventListener('livewire:navigating', stop);
            window.addEventListener('pagehide', stop);

            if (this.embedded) {
                this.$nextTick(() => this.openScanner());
            }
        },

        resolveWire() {
            if (this.$wire && typeof this.$wire.processScannedCode === 'function') {
                return this.$wire;
            }

            const host = this.$el.closest('[wire\\:id]');
            if (!host || !window.Livewire) {
                return null;
            }

            const id = host.getAttribute('wire:id');
            if (!id) {
                return null;
            }

            if (typeof window.Livewire.find === 'function') {
                return window.Livewire.find(id);
            }

            return null;
        },

        async callProcessScannedCode(code) {
            const wire = this.resolveWire();
            if (!wire) {
                throw new Error('Tidak dapat terhubung ke server. Muat ulang halaman.');
            }

            if (typeof wire.processScannedCode === 'function') {
                return wire.processScannedCode(code);
            }

            if (typeof wire.call === 'function') {
                return wire.call('processScannedCode', code);
            }

            throw new Error('Livewire tidak mendukung pemrosesan scan.');
        },

        async waitForLayout() {
            if (typeof this.$nextTick === 'function') {
                await this.$nextTick();
            }

            await new Promise((resolve) => {
                requestAnimationFrame(() => requestAnimationFrame(resolve));
            });
        },

        lockScroll() {
            if (this.embedded) {
                return;
            }

            document.body.style.overflow = 'hidden';
        },

        unlockScroll() {
            if (this.embedded) {
                return;
            }

            document.body.style.overflow = '';
        },

        normalizeScannerVideo() {
            const root = document.getElementById(this.regionId);
            if (!root) {
                return;
            }

            root.querySelectorAll('video').forEach((video) => {
                video.style.width = '100%';
                video.style.height = '100%';
                video.style.minHeight = '100%';
                video.style.objectFit = 'cover';
                video.style.display = 'block';
            });
        },

        async openScanner() {
            if (this.open || this.starting || this.processing) {
                return;
            }

            this.error = null;
            this.open = true;
            this.lockScroll();
            this.starting = true;

            try {
                if (!window.isSecureContext) {
                    throw new Error('Kamera hanya bisa dipakai lewat HTTPS atau localhost.');
                }

                if (!navigator.mediaDevices?.getUserMedia) {
                    throw new Error('Browser ini tidak mendukung akses kamera.');
                }

                await this.waitForLayout();

                this.scanner = new Html5Qrcode(this.regionId);
                await this.scanner.start(
                    { facingMode: 'environment' },
                    {
                        fps: 10,
                        qrbox: (viewfinderWidth, viewfinderHeight) => {
                            const size = Math.min(
                                Math.max(Math.floor(Math.min(viewfinderWidth, viewfinderHeight) * 0.72), 180),
                                this.embedded ? 260 : 320,
                            );

                            return { width: size, height: size };
                        },
                        formatsToSupport: SCANNER_FORMATS,
                        experimentalFeatures: {
                            useBarCodeDetectorIfSupported: true,
                        },
                    },
                    (decodedText) => this.onDecoded(decodedText),
                    () => {},
                );

                this.normalizeScannerVideo();
            } catch (err) {
                this.error = err?.message || String(err);
                await this.closeScanner();
            } finally {
                this.starting = false;
            }
        },

        async stopCamera() {
            if (!this.scanner) {
                return;
            }

            try {
                await this.scanner.stop();
            } catch {
                // Scanner may already be stopped.
            }

            try {
                await this.scanner.clear();
            } catch {
                // Ignore clear errors during teardown.
            }

            this.scanner = null;
        },

        async closeScanner() {
            await this.stopCamera();
            this.open = false;
            this.starting = false;
            this.unlockScroll();
        },

        async onDecoded(decodedText) {
            const code = String(decodedText || '').trim();
            if (code === '' || this.processing) {
                return;
            }

            const now = Date.now();
            if (code === this.lastScan && now - this.lastScanAt < this.cooldownMs) {
                return;
            }

            this.lastScan = code;
            this.lastScanAt = now;
            this.processing = true;
            this.error = null;

            if (!this.embedded) {
                await this.closeScanner();
            }

            try {
                await this.callProcessScannedCode(code);
            } catch (err) {
                this.error = err?.message || 'Gagal memproses QR code.';
            } finally {
                this.processing = false;
            }
        },
    };
}

function registerEventCheckinScanner() {
    if (!window.Alpine || window.__eventCheckinScannerRegistered) {
        return;
    }

    window.__eventCheckinScannerRegistered = true;
    window.Alpine.data('eventCheckinScanner', (regionId, options = {}) => scannerDataFactory(regionId, options));
}

document.addEventListener('alpine:init', registerEventCheckinScanner);

if (window.Alpine) {
    registerEventCheckinScanner();
}
