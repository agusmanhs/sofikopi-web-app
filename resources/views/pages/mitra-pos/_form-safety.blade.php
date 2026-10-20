{{--
    Pengaman form bersama untuk modul Mitra POS. Sertakan SEKALI per halaman di
    dalam @section('page-script') (setelah SweetAlert dimuat di vendor-script):

        @include('pages.mitra-pos._form-safety')

    Perilakunya aktif lewat atribut, jadi form yang tidak memakainya tidak berubah:
      - input.rupiah-input        : topeng ribuan "15.000.000" (tanpa sen)
      - form[data-lock-submit]    : tombol simpan dikunci setelah diklik (cegah klik ganda)
      - form[data-confirm-title]  : tampilkan dialog konfirmasi sebelum form dikirim
          data-confirm-text="baris 1|baris 2"  (pisahkan baris dengan "|")
          data-confirm-button="Ya, simpan"
          data-confirm-danger  (tombol konfirmasi merah)
    Untuk konfirmasi dengan ringkasan dinamis, panggil MitraForm.confirm({...}).
--}}
<script>
    (function() {
        if (window.MitraForm) return;

        const escapeHtml = function(value) {
            return String(value).replace(/[&<>"']/g, function(ch) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
            });
        };

        // Nilai desimal dari database ("15000000.00" / "6000.5") dibulatkan ke rupiah
        // utuh. Teks yang sudah bertopeng ("15.000.000") selalu punya 3 digit setelah
        // titik, jadi tidak pernah cocok dengan pola ini.
        const toRupiahNumber = function(raw) {
            const text = String(raw == null ? '' : raw).trim();
            if (/^\d+\.\d{1,2}$/.test(text)) {
                return Math.round(parseFloat(text));
            }
            const digits = text.replace(/[^\d]/g, '');
            return digits ? Number(digits) : null;
        };

        const formatRupiah = function(number) {
            return number === null || number === undefined || isNaN(number)
                ? ''
                : Number(number).toLocaleString('id-ID');
        };

        const buildHtml = function(lines) {
            return '<div class="text-start">' + lines.map(function(line) {
                return '<p class="mb-1">' + escapeHtml(line) + '</p>';
            }).join('') + '</div>';
        };

        // Mengembalikan Promise<boolean>. Tanpa SweetAlert jatuh ke confirm() bawaan
        // supaya aksi tetap tidak lolos tanpa konfirmasi.
        const confirmAction = function(options) {
            const lines = options.lines || [];
            if (typeof Swal === 'undefined') {
                return Promise.resolve(window.confirm([options.title].concat(lines).join('\n')));
            }
            return Swal.fire({
                title: options.title,
                html: buildHtml(lines),
                icon: options.danger ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonText: options.confirmText || 'Ya, lanjutkan',
                cancelButtonText: options.cancelText || 'Periksa lagi',
                reverseButtons: true,
                focusCancel: !!options.danger,
                customClass: {
                    confirmButton: 'btn ' + (options.danger ? 'btn-danger' : 'btn-primary') + ' ms-2',
                    cancelButton: 'btn btn-label-secondary',
                },
                buttonsStyling: false,
            }).then(function(result) {
                return result.isConfirmed;
            });
        };

        const lockForm = function(form, submitEvent) {
            form.dataset.submitting = '1';
            // Ditunda satu tick: tombol yang di-disable seketika membuat form
            // tidak terkirim di sebagian browser. Kalau handler lain membatalkan
            // submit (form dikirim lewat fetch / gagal validasi sendiri), jangan
            // kunci — tombol akan macet selamanya.
            setTimeout(function() {
                if (submitEvent && submitEvent.defaultPrevented) {
                    delete form.dataset.submitting;
                    return;
                }
                form.querySelectorAll('[type="submit"]').forEach(function(button) {
                    button.dataset.originalHtml = button.innerHTML;
                    button.disabled = true;
                    button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';
                });
            }, 0);
        };

        const unlockForm = function(form) {
            delete form.dataset.submitting;
            delete form.dataset.confirmed;
            form.querySelectorAll('[type="submit"]').forEach(function(button) {
                if (button.dataset.originalHtml) {
                    button.innerHTML = button.dataset.originalHtml;
                }
                button.disabled = false;
            });
        };

        // Fase capture supaya berjalan sebelum handler submit milik halaman.
        document.addEventListener('submit', function(event) {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) return;

            if (form.dataset.submitting === '1') {
                event.preventDefault();
                event.stopImmediatePropagation();
                return;
            }

            if (form.hasAttribute('data-confirm-title') && form.dataset.confirmed !== '1') {
                event.preventDefault();
                event.stopImmediatePropagation();
                confirmAction({
                    title: form.getAttribute('data-confirm-title'),
                    lines: (form.getAttribute('data-confirm-text') || '').split('|').filter(Boolean),
                    confirmText: form.getAttribute('data-confirm-button') || 'Ya, simpan',
                    danger: form.hasAttribute('data-confirm-danger'),
                }).then(function(ok) {
                    if (!ok) return;
                    form.dataset.confirmed = '1';
                    form.requestSubmit(event.submitter || undefined);
                });
                return;
            }

            if (form.hasAttribute('data-lock-submit')) {
                lockForm(form, event);
            }
        }, true);

        // Tombol "kembali" browser memulihkan halaman dari cache dengan tombol masih terkunci.
        window.addEventListener('pageshow', function(event) {
            if (!event.persisted) return;
            document.querySelectorAll('form[data-lock-submit]').forEach(unlockForm);
        });

        const initRupiahInputs = function(root) {
            (root || document).querySelectorAll('input.rupiah-input').forEach(function(input) {
                if (input.dataset.rupiahReady) return;
                input.dataset.rupiahReady = '1';
                input.setAttribute('inputmode', 'numeric');
                input.setAttribute('autocomplete', 'off');
                input.value = formatRupiah(toRupiahNumber(input.value));
                input.addEventListener('input', function() {
                    const digits = input.value.replace(/[^\d]/g, '');
                    input.value = digits ? formatRupiah(Number(digits)) : '';
                });
            });
        };

        window.MitraForm = {
            escapeHtml: escapeHtml,
            toRupiahNumber: toRupiahNumber,
            formatRupiah: formatRupiah,
            confirm: confirmAction,
            lock: lockForm,
            unlock: unlockForm,
            initRupiahInputs: initRupiahInputs,
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() { initRupiahInputs(); });
        } else {
            initRupiahInputs();
        }
    })();
</script>
