document.addEventListener('DOMContentLoaded', () => {
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
