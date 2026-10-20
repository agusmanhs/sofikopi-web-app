{{--
    Pengaman form bersama untuk modul Mitra POS. Sertakan SEKALI per halaman di
    dalam @section('page-script') (setelah SweetAlert dimuat di vendor-script):

        @include('pages.mitra-pos._form-safety')

    Perilakunya aktif lewat atribut, jadi form yang tidak memakainya tidak berubah:
      - input.rupiah-input        : topeng ribuan "15.000.000" (tanpa sen)
          data-decimals="2"       : izinkan koma desimal "18.500,5" (harga per gram, dsb.)
      - form[data-live-filter]    : filter daftar langsung jalan tanpa tombol Cari/Reset
          data-live-target="#live-results"  (elemen yang diganti; bawaan #live-results)
          data-live-delay="350"             (jeda ketik dalam ms)
          Kotak hasil (tabel + ringkasan + paginasi) harus dibungkus elemen target.
          Tautan paginasi di dalam target ikut dimuat tanpa reload layar penuh.
      - select.js-select2         : dropdown yang bisa dicari (Select2) untuk pilihan dari database
          data-placeholder="-- Pilih bahan --"
          data-allow-clear        : tampilkan tombol hapus pilihan (butuh <option value=""> kosong)
          Halaman wajib memuat select2.js/select2.scss di vendor-script/vendor-style.
          Select di dalam .modal otomatis memakai modal sebagai dropdownParent.
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

        // Seperti toRupiahNumber tetapi mempertahankan desimal ("18,5" -> 18.5).
        const toDecimalNumber = function(raw) {
            const text = String(raw == null ? '' : raw).trim();
            if (text === '') return null;
            if (/^\d+\.\d{1,2}$/.test(text)) return parseFloat(text);
            const number = parseFloat(text.replace(/\./g, '').replace(',', '.').replace(/[^\d.]/g, ''));
            return isNaN(number) ? null : number;
        };

        const formatDecimal = function(number, decimals) {
            return number === null || number === undefined || isNaN(number)
                ? ''
                : Number(number).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: decimals });
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
                title: escapeHtml(options.title),
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
                    // requestSubmit memicu event submit secara sinkron. Kalau validasi
                    // browser menolak, penanda dihapus supaya klik berikutnya
                    // tetap menampilkan konfirmasi.
                    delete form.dataset.confirmed;
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
                const decimals = parseInt(input.dataset.decimals || '0', 10);
                if (decimals > 0) {
                    input.setAttribute('inputmode', 'decimal');
                    input.value = formatDecimal(toDecimalNumber(input.value), decimals);
                    input.addEventListener('input', function() {
                        const cleaned = input.value.replace(/[^\d,]/g, '');
                        const comma = cleaned.indexOf(',');
                        const whole = comma === -1 ? cleaned : cleaned.slice(0, comma);
                        const fraction = comma === -1 ? null : cleaned.slice(comma + 1).replace(/,/g, '').slice(0, decimals);
                        const wholeText = whole ? Number(whole).toLocaleString('id-ID') : (fraction !== null ? '0' : '');
                        input.value = fraction === null ? wholeText : wholeText + ',' + fraction;
                    });
                    return;
                }
                input.value = formatRupiah(toRupiahNumber(input.value));
                input.addEventListener('input', function() {
                    const digits = input.value.replace(/[^\d]/g, '');
                    input.value = digits ? formatRupiah(Number(digits)) : '';
                });
            });
        };

        const initSelect2 = function(root) {
            const jq = window.jQuery || window.$;
            if (!jq || !jq.fn || !jq.fn.select2) return;
            jq(root || document).find('select.js-select2').each(function() {
                const $select = jq(this);
                if ($select.data('select2')) return;
                const $modal = $select.closest('.modal');
                $select.select2({
                    width: '100%',
                    placeholder: $select.attr('data-placeholder') || 'Pilih...',
                    allowClear: $select.is('[data-allow-clear]'),
                    dropdownParent: $modal.length ? $modal : jq(document.body),
                    language: {
                        noResults: function() { return 'Tidak ditemukan'; },
                        searching: function() { return 'Mencari...'; },
                    },
                });
            });
        };

        window.MitraForm = {
            initSelect2: initSelect2,
            escapeHtml: escapeHtml,
            toRupiahNumber: toRupiahNumber,
            toDecimalNumber: toDecimalNumber,
            formatDecimal: formatDecimal,
            formatRupiah: formatRupiah,
            confirm: confirmAction,
            lock: lockForm,
            unlock: unlockForm,
            initRupiahInputs: initRupiahInputs,
        };

        // Satu listener klik untuk semua blok hasil (bukan satu per form), supaya
        // paginasi satu blok tidak dibajak form lain di halaman yang sama.
        const liveRegions = [];
        let liveClickBound = false;

        const bindLiveClicks = function() {
            if (liveClickBound) return;
            liveClickBound = true;
            document.addEventListener('click', function(event) {
                if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey) return;
                const link = event.target.closest ? event.target.closest('a.page-link') : null;
                if (!link || !link.href || link.target || link.hasAttribute('download')) return;
                for (let i = 0; i < liveRegions.length; i++) {
                    const region = liveRegions[i];
                    if (link.closest(region.selector)) {
                        event.preventDefault();
                        region.load(link.href, { scroll: true });
                        return;
                    }
                }
            });
        };

        const initLiveFilters = function() {
            document.querySelectorAll('form[data-live-filter]').forEach(function(form) {
                if (form.dataset.liveReady) return;
                form.dataset.liveReady = '1';

                const targetSelector = form.getAttribute('data-live-target') || '#live-results';
                const delay = parseInt(form.getAttribute('data-live-delay') || '350', 10);
                let timer = null;
                let controller = null;
                let lastUrl = null;
                let lastAt = 0;

                const buildUrl = function() {
                    const params = new URLSearchParams();
                    new FormData(form).forEach(function(value, key) {
                        if (typeof value === 'string' && value.trim() !== '') {
                            params.append(key, value.trim());
                        }
                    });
                    const base = form.getAttribute('action') || window.location.pathname;
                    const query = params.toString();
                    return query ? base + (base.indexOf('?') === -1 ? '?' : '&') + query : base;
                };

                const load = function(url, options) {
                    const box = document.querySelector(targetSelector);
                    if (!box) {
                        window.location.href = url;
                        return;
                    }
                    if (controller) controller.abort();
                    const mine = new AbortController();
                    controller = mine;
                    box.style.opacity = '0.5';
                    box.setAttribute('aria-busy', 'true');

                    fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                        credentials: 'same-origin',
                        signal: mine.signal,
                    }).then(function(response) {
                        // Redirect berarti server menolak/mengalihkan (validasi gagal, sesi
                        // habis). Hasilnya bukan untuk URL ini — buka halaman penuh supaya
                        // pesan error tampil dan alamat sesuai isi layar.
                        if (response.redirected) throw new Error('redirect');
                        if (!response.ok) throw new Error('HTTP ' + response.status);
                        return response.text();
                    }).then(function(html) {
                        const fresh = new DOMParser().parseFromString(html, 'text/html').querySelector(targetSelector);
                        if (!fresh) throw new Error('target tidak ada');
                        box.innerHTML = fresh.innerHTML;
                        window.history.replaceState(null, '', url);
                        initSelect2(box);
                        initRupiahInputs(box);
                        if (options && options.scroll) {
                            box.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    }).catch(function(error) {
                        if (error && error.name === 'AbortError') return;
                        window.location.href = url;
                    }).finally(function() {
                        // Permintaan yang dibatalkan tidak boleh mematikan indikator
                        // permintaan yang menggantikannya.
                        if (controller === mine) {
                            box.style.opacity = '';
                            box.removeAttribute('aria-busy');
                        }
                    });
                };

                liveRegions.push({ selector: targetSelector, load: load });
                bindLiveClicks();

                const run = function() {
                    clearTimeout(timer);
                    const url = buildUrl();
                    // Select2 memicu change lebih dari sekali; abaikan pemicu kembar.
                    if (url === lastUrl && Date.now() - lastAt < 150) return;
                    lastUrl = url;
                    lastAt = Date.now();
                    load(url);
                };

                form.addEventListener('submit', function(event) {
                    event.preventDefault();
                    run();
                });

                form.addEventListener('input', function(event) {
                    if (event.target.matches('input[type="text"], input[type="search"], input:not([type])')) {
                        clearTimeout(timer);
                        timer = setTimeout(run, delay);
                    }
                });

                form.addEventListener('change', function(event) {
                    if (event.target.matches('select, input[type="date"], input[type="checkbox"], input[type="radio"]')) {
                        run();
                    }
                });

                const jq = window.jQuery || window.$;
                if (jq) {
                    jq(form).on('change', 'select', run);
                }

            });
        };

        window.MitraForm.initLiveFilters = initLiveFilters;

        const initAll = function() {
            initRupiahInputs();
            initSelect2();
            initLiveFilters();
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initAll);
        } else {
            initAll();
        }
    })();
</script>
