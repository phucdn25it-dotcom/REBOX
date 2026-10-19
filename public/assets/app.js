document.addEventListener('DOMContentLoaded', () => {
    const fontChoices = document.querySelectorAll('[data-font-choice]');
    const savedFont = localStorage.getItem('rebox-font') || 'default';
    document.body.dataset.reboxFont = savedFont;
    fontChoices.forEach((select) => {
        select.value = savedFont;
        select.addEventListener('change', () => {
            document.body.dataset.reboxFont = select.value;
            localStorage.setItem('rebox-font', select.value);
            fontChoices.forEach((other) => { other.value = select.value; });
        });
    });

    const scanButton = document.getElementById('start-qr-scan');
    const scanVideo = document.getElementById('staff-qr-video');
    const scanInput = document.getElementById('staff-qr-token');
    const scanStatus = document.getElementById('staff-qr-status');
    if (scanButton && scanVideo && scanInput) {
        let scanStream;
        scanButton.addEventListener('click', async () => {
            if (!('BarcodeDetector' in window) || !navigator.mediaDevices?.getUserMedia) {
                if (scanStatus) scanStatus.textContent = 'Trình duyệt chưa hỗ trợ quét QR bằng camera. Hãy nhập token thủ công.';
                return;
            }
            try {
                const detector = new BarcodeDetector({ formats: ['qr_code'] });
                scanStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } } });
                scanVideo.srcObject = scanStream;
                scanVideo.hidden = false;
                await scanVideo.play();
                if (scanStatus) scanStatus.textContent = 'Đưa mã QR vào khung hình để quét.';
                const scan = async () => {
                    if (!scanStream?.active) return;
                    try {
                        const codes = await detector.detect(scanVideo);
                        if (codes[0]?.rawValue) {
                            scanInput.value = codes[0].rawValue.trim();
                            scanStream.getTracks().forEach((track) => track.stop());
                            scanStream = null;
                            scanVideo.hidden = true;
                            if (scanStatus) scanStatus.textContent = 'Đã đọc mã QR. Chọn thao tác rồi ghi nhận.';
                            return;
                        }
                    } catch (_) { /* wait for next video frame */ }
                    requestAnimationFrame(scan);
                };
                requestAnimationFrame(scan);
            } catch (_) {
                if (scanStatus) scanStatus.textContent = 'Không mở được camera. Hãy cấp quyền camera hoặc nhập token thủ công.';
            }
        });
    }
    const mobileToggle = document.getElementById('mobile-menu-toggle');
    const mainNavigation = document.getElementById('main-navigation');
    if (mobileToggle && mainNavigation) {
        const setMobileMenu = (open) => {
            document.body.classList.toggle('menu-open', open);
            mobileToggle.setAttribute('aria-expanded', String(open));
            mobileToggle.setAttribute('aria-label', open ? 'Đóng menu' : 'Mở menu');
            mobileToggle.textContent = open ? '×' : '☰';
        };
        mobileToggle.addEventListener('click', () => {
            setMobileMenu(!document.body.classList.contains('menu-open'));
        });
        mainNavigation.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => setMobileMenu(false));
        });
        document.addEventListener('pointerdown', (event) => {
            if (document.body.classList.contains('menu-open') &&
                !mainNavigation.contains(event.target) &&
                !mobileToggle.contains(event.target)) setMobileMenu(false);
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') setMobileMenu(false);
        });
        window.addEventListener('resize', () => {
            if (window.innerWidth > 760) setMobileMenu(false);
        }, { passive: true });
    }

    document.querySelectorAll('[data-qrcode]').forEach((element) => {
        if (window.QRCode) {
            new QRCode(element, {
                text: element.dataset.qrcode,
                width: 64,
                height: 64,
                colorDark: '#183f33',
                colorLight: '#e9eee3',
                correctLevel: QRCode.CorrectLevel.M,
            });
        }
    });

    const flash = document.querySelector('.flash');
    if (flash) setTimeout(() => flash.remove(), 4200);

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!confirm(form.dataset.confirm)) event.preventDefault();
        });
    });

    document.querySelectorAll('[data-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            const text = button.dataset.copy || '';
            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(text);
                } else {
                    const input = document.createElement('textarea');
                    input.value = text;
                    input.style.position = 'fixed';
                    input.style.opacity = '0';
                    document.body.append(input);
                    input.select();
                    document.execCommand('copy');
                    input.remove();
                }
                button.textContent = 'Đã sao chép';
            } catch (_) {
                button.textContent = 'Không thể sao chép';
            }
        });
    });
});
