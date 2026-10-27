/*
 * Layer bahaya InaRISK BNPB untuk peta Leaflet SIGAPDESA.
 *
 * - Overlay 12 layer bahaya (ImageServer `exportImage`, per tile bbox Web Mercator).
 * - Klik peta → tingkat bahaya di titik itu (lewat proxy /inarisk/hazard-at).
 * - Rumah yang zonanya belum dianalisis otomatis dihitung dari InaRISK
 *   (lewat POST /inarisk/zona/{id}).
 *
 * Pemakaian (setelah peta dibuat):
 *   SigapInaRisk.attach(map, { panel: '#filter-panel', rumah: rawRumahData, onZonaUpdated: renderMapMarkers });
 *
 * Konfigurasi URL & CSRF diisi oleh layouts/admin.blade.php di window.SIGAP_INARISK_CONFIG.
 */
(function (window, document) {
    'use strict';

    var BASE = 'https://gis.bnpb.go.id/server/rest/services/inarisk';

    // Indeks 0–1 → kuning-merah (likuefaksi tidak punya layer berwarna di InaRISK).
    var YELLOW_TO_RED = '{"rasterFunction":"Colormap","rasterFunctionArguments":{"ColorrampName":' +
        '"Yellow to Red","Raster":{"rasterFunction":"Stretch","rasterFunctionArguments":' +
        '{"StretchType":5,"Min":0,"Max":255,"DRA":false,"UseGamma":false,' +
        '"Statistics":[[0,1,0.5,0.25]]},"outputPixelType":"U8"}},"outputPixelType":"U8"}';

    // id sama dengan config/inarisk.php
    var LAYERS = [
        { id: 'banjir',           label: 'Banjir',             service: 'layer_bahaya_banjir' },
        { id: 'tanah_longsor',    label: 'Tanah longsor',      service: 'layer_bahaya_tanah_longsor' },
        { id: 'gempabumi',        label: 'Gempa bumi',         service: 'layer_bahaya_gempabumi' },
        { id: 'tsunami',          label: 'Tsunami',            service: 'layer_bahaya_tsunami' },
        { id: 'banjir_bandang',   label: 'Banjir bandang',     service: 'layer_bahaya_banjir_bandang' },
        { id: 'cuaca_ekstrim',    label: 'Cuaca ekstrem',      service: 'layer_bahaya_cuaca_ekstrim' },
        { id: 'gelombang_abrasi', label: 'Gelombang & abrasi', service: 'layer_bahaya_gelombang_ekstrim_dan_abrasi' },
        { id: 'kekeringan',       label: 'Kekeringan',         service: 'layer_bahaya_kekeringan' },
        { id: 'karhutla',         label: 'Kebakaran hutan',    service: 'layer_bahaya_kebakaran_hutan_dan_lahan' },
        { id: 'gunung_api',       label: 'Gunung api',         service: 'layer_bahaya_letusan_gunungapi' },
        { id: 'likuefaksi',       label: 'Likuefaksi',         service: 'INDEKS_BAHAYA_LIKUEFAKSI', renderingRule: YELLOW_TO_RED },
        { id: 'multi',            label: 'Multi bahaya',       service: 'layer_bahaya_multi' }
    ];

    var LEVELS = {
        rendah: { label: 'Rendah', color: '#16A34A' },
        sedang: { label: 'Sedang', color: '#EA580C' },
        tinggi: { label: 'Tinggi', color: '#DC2626' }
    };

    var STORAGE_KEY = 'sigap-inarisk';

    function layerById(id) {
        for (var i = 0; i < LAYERS.length; i++) if (LAYERS[i].id === id) return LAYERS[i];
        return null;
    }

    function config() { return window.SIGAP_INARISK_CONFIG || {}; }

    function loadPrefs() {
        try { return JSON.parse(window.localStorage.getItem(STORAGE_KEY)) || {}; } catch (e) { return {}; }
    }

    function savePrefs(p) {
        try { window.localStorage.setItem(STORAGE_KEY, JSON.stringify(p)); } catch (e) { /* abaikan */ }
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    /*
     * Tile dari ArcGIS ImageServer `exportImage`. ImageServer tidak punya tile
     * siap pakai; tiap kotak peta diubah jadi permintaan gambar dengan bbox
     * EPSG:3857. Server InaRISK lambat & menolak banyak permintaan sekaligus,
     * jadi dibatasi 2 unduhan paralel, dicoba ulang 3× (0,8 → 1,6 → 3,2 dtk),
     * dan tile yang sudah tidak terlihat dibatalkan dari antrean.
     */
    var layerClassCache = null;
    function InaRiskTileLayerClass() {
        if (layerClassCache && layerClassCache.L === window.L) return layerClassCache.cls;
        var L = window.L;

        var cls = L.GridLayer.extend({
            options: { tileSize: 512, opacity: 0.6, maxConcurrent: 2, retries: 3, zIndex: 250 },

            initialize: function (layer, options) {
                L.GridLayer.prototype.initialize.call(this, options);
                this._layer = layer;
                this._queue = [];
                this._active = 0;
                this.pending = 0;
                this.failed = 0;
                this.on('tileunload', function (e) { if (e.tile._job) e.tile._job.cancelled = true; });
            },

            createTile: function (coords, done) {
                var img = document.createElement('img');
                img.alt = '';
                img.setAttribute('role', 'presentation');
                var job = { img: img, url: this._urlFor(coords), done: done, attempt: 0, cancelled: false };
                img._job = job;
                this._setPending(+1);
                this._queue.push(job);
                // Ditunda: Leaflet memanggil createTile untuk banyak tile sekaligus.
                var self = this;
                setTimeout(function () { self._pump(); }, 0);
                return img;
            },

            _urlFor: function (coords) {
                var size = this.getTileSize();
                var crs = L.CRS.EPSG3857;
                var nw = crs.project(this._map.unproject(coords.scaleBy(size), coords.z));
                var se = crs.project(this._map.unproject(coords.add([1, 1]).scaleBy(size), coords.z));
                var params = {
                    bbox: [nw.x, se.y, se.x, nw.y].map(function (v) { return v.toFixed(2); }).join(','),
                    bboxSR: 3857,
                    imageSR: 3857,
                    size: size.x + ',' + size.y,
                    format: 'png32',
                    transparent: 'true',
                    f: 'image'
                };
                if (this._layer.renderingRule) params.renderingRule = this._layer.renderingRule;
                var qs = Object.keys(params).map(function (k) {
                    return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
                }).join('&');
                return BASE + '/' + this._layer.service + '/ImageServer/exportImage?' + qs;
            },

            _pump: function () {
                while (this._active < this.options.maxConcurrent && this._queue.length) {
                    var job = this._queue.shift();
                    if (job.cancelled) { this._setPending(-1); continue; }
                    this._start(job);
                }
            },

            _start: function (job) {
                var self = this;
                var img = job.img;
                this._active++;

                img.onload = function () {
                    self._active--;
                    self._setPending(-1);
                    if (!job.cancelled) job.done(null, img);
                    self._pump();
                };
                img.onerror = function () {
                    self._active--;
                    if (!job.cancelled && job.attempt < self.options.retries) {
                        var delay = 800 * Math.pow(2, job.attempt);
                        job.attempt++;
                        setTimeout(function () { self._queue.push(job); self._pump(); }, delay);
                    } else {
                        self._setPending(-1);
                        if (!job.cancelled) { self.failed++; job.done(new Error('Tile InaRISK gagal'), img); }
                    }
                    self._pump();
                };
                img.src = job.attempt ? job.url + '&_retry=' + job.attempt : job.url;
            },

            _setPending: function (delta) {
                this.pending = Math.max(0, this.pending + delta);
                this.fire('pendingchange', { pending: this.pending, failed: this.failed });
            },

            reload: function () {
                this.failed = 0;
                this.redraw();
            }
        });

        layerClassCache = { L: L, cls: cls };
        return cls;
    }

    function panelHtml() {
        var options = LAYERS.map(function (l) {
            return '<option value="' + l.id + '">' + escapeHtml(l.label) + '</option>';
        }).join('');
        var legend = Object.keys(LEVELS).map(function (k) {
            return '<span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full" style="background:' +
                LEVELS[k].color + '"></span>' + LEVELS[k].label + '</span>';
        }).join('');

        return '' +
            '<span class="font-extrabold text-gray-700 uppercase text-[10px] block">Layer Bahaya InaRISK (BNPB):</span>' +
            '<select class="sigap-inarisk-select select select-bordered select-xs w-full bg-white text-slate-900 font-bold">' +
                '<option value="">— Tidak ditampilkan —</option>' + options +
            '</select>' +
            '<div class="sigap-inarisk-detail hidden space-y-1.5">' +
                '<label class="flex items-center gap-2 text-[10px] font-bold text-gray-600">Transparansi' +
                    '<input type="range" min="20" max="100" step="10" class="sigap-inarisk-opacity range range-xs range-error flex-1"></label>' +
                '<div class="flex flex-wrap gap-x-2 gap-y-0.5 text-[10px] font-bold text-gray-600">' + legend + '</div>' +
                '<p class="text-[10px] text-gray-500 leading-snug"><i class="fas fa-hand-pointer"></i> Klik peta untuk melihat tingkat bahaya di titik itu.</p>' +
            '</div>' +
            '<div class="sigap-inarisk-status text-[10px] font-bold leading-snug"></div>' +
            '<div class="sigap-inarisk-zona text-[10px] font-bold leading-snug"></div>';
    }

    // Satu sinkronisasi zona per halaman, walau attach() dipanggil ulang.
    var zonaSync = { started: false, panels: [] };

    function setZonaStatus(html) {
        zonaSync.panels.forEach(function (el) { if (document.body.contains(el)) el.innerHTML = html; });
    }

    function syncZona(rumah, onZonaUpdated) {
        if (zonaSync.started) return;
        var cfg = config();
        if (!cfg.zonaUrl || !rumah) return;

        var todo = rumah.filter(function (r) { return r.perlu_analisis && r.id; });
        if (!todo.length) return;
        zonaSync.started = true;

        var i = 0, gagal = 0;
        function next() {
            if (i >= todo.length) {
                setZonaStatus(gagal
                    ? '<span class="text-amber-600"><i class="fas fa-exclamation-triangle"></i> ' + gagal +
                      ' rumah belum bisa dianalisis (server InaRISK sibuk). Muat ulang halaman nanti.</span>'
                    : '<span class="text-emerald-600"><i class="fas fa-check-circle"></i> Zona rumah sudah diperbarui dari InaRISK.</span>');
                return;
            }
            var r = todo[i];
            setZonaStatus('<span class="text-sky-600"><i class="fas fa-spinner fa-spin"></i> Menganalisis zona rumah ' +
                (i + 1) + '/' + todo.length + ' dari InaRISK…</span>');

            fetch(cfg.zonaUrl.replace('__ID__', r.id), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': cfg.csrf, 'Accept': 'application/json' },
                credentials: 'same-origin'
            }).then(function (res) {
                return res.json().then(function (body) { if (!res.ok) throw new Error(body.message); return body; });
            }).then(function (hasil) {
                r.perlu_analisis = false;
                r.indeks_bahaya = hasil.indeks;
                if (hasil.zona) { r.zona_bencana = hasil.zona; r.zona = hasil.zona; }
                if (typeof onZonaUpdated === 'function') onZonaUpdated(r);
            }).catch(function () {
                gagal++;
            }).then(function () {
                i++;
                next();
            });
        }
        next();
    }

    function queryHazardAt(map, layer, latlng) {
        var cfg = config();
        if (!cfg.hazardAtUrl) return;
        var popup = window.L.popup({ maxWidth: 240 })
            .setLatLng(latlng)
            .setContent('<div class="text-xs text-slate-700"><i class="fas fa-spinner fa-spin"></i> Memuat tingkat bahaya ' +
                escapeHtml(layer.label) + '…<br><span class="text-[10px] text-slate-400">Server InaRISK bisa butuh hingga 1 menit.</span></div>')
            .openOn(map);

        var url = cfg.hazardAtUrl + '?' + new URLSearchParams({ id: layer.id, lat: latlng.lat.toFixed(6), lng: latlng.lng.toFixed(6) });
        fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (res) {
                return res.json().then(function (body) { if (!res.ok) throw new Error(body.message || 'Gagal'); return body; });
            })
            .then(function (h) {
                var lv = h.level ? LEVELS[h.level] : null;
                var badge = lv
                    ? '<span class="px-2 py-0.5 rounded text-[10px] font-bold text-white" style="background:' + lv.color + '">' + lv.label.toUpperCase() + '</span>'
                    : '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200 text-slate-700">DI LUAR AREA BAHAYA</span>';
                popup.setContent('<div class="text-xs text-slate-800 space-y-1">' +
                    '<div class="font-black">Bahaya ' + escapeHtml(h.label) + '</div>' +
                    '<div>Tingkat: ' + badge + '</div>' +
                    (h.indeks !== null ? '<div class="text-[10px] text-slate-500">Indeks InaRISK: ' + Number(h.indeks).toFixed(3) + ' (0–1)</div>' : '') +
                    '<div class="text-[10px] text-slate-400">Sumber: InaRISK BNPB</div></div>');
            })
            .catch(function (e) {
                popup.setContent('<div class="text-xs text-rose-600"><i class="fas fa-exclamation-triangle"></i> ' + escapeHtml(e.message) + '</div>');
            });
    }

    function attach(map, opts) {
        opts = opts || {};
        var L = window.L;
        var panel = typeof opts.panel === 'string' ? document.querySelector(opts.panel) : opts.panel;
        var prefs = loadPrefs();
        var current = null;    // { layer, tiles }

        var box = null;
        if (panel) {
            var old = panel.querySelector('.sigap-inarisk');
            if (old) old.remove();
            box = document.createElement('div');
            box.className = 'sigap-inarisk space-y-1.5 bg-rose-50/60 p-2 rounded-xl border border-rose-100';
            box.innerHTML = panelHtml();
            var heading = panel.firstElementChild;
            if (heading && heading.nextSibling) panel.insertBefore(box, heading.nextSibling); else panel.appendChild(box);
            // Jangan teruskan klik/scroll panel ke peta.
            if (L.DomEvent) { L.DomEvent.disableClickPropagation(box); L.DomEvent.disableScrollPropagation(box); }
        }

        var select = box && box.querySelector('.sigap-inarisk-select');
        var opacity = box && box.querySelector('.sigap-inarisk-opacity');
        var detail = box && box.querySelector('.sigap-inarisk-detail');
        var status = box && box.querySelector('.sigap-inarisk-status');
        if (box) zonaSync.panels.push(box.querySelector('.sigap-inarisk-zona'));

        function renderStatus(tiles) {
            if (!status) return;
            if (tiles.pending > 0) {
                status.innerHTML = '<span class="text-sky-600"><i class="fas fa-spinner fa-spin"></i> Memuat layer bahaya… (' + tiles.pending + ')</span>';
            } else if (tiles.failed > 0) {
                status.innerHTML = '<span class="text-amber-600"><i class="fas fa-exclamation-triangle"></i> Sebagian layer gagal dimuat. ' +
                    '<button type="button" class="sigap-inarisk-reload underline">Muat ulang</button></span>';
                status.querySelector('.sigap-inarisk-reload').onclick = function () { tiles.reload(); };
            } else {
                status.innerHTML = '';
            }
        }

        function show(id) {
            if (current) { map.removeLayer(current.tiles); current = null; }
            var layer = layerById(id);
            if (detail) detail.classList.toggle('hidden', !layer);
            if (status) status.innerHTML = '';
            prefs.layer = layer ? layer.id : '';
            savePrefs(prefs);
            if (!layer) return;

            var Cls = InaRiskTileLayerClass();
            var tiles = new Cls(layer, { opacity: (prefs.opacity || 60) / 100 });
            tiles.on('pendingchange', function () { renderStatus(tiles); });
            tiles.addTo(map);
            current = { layer: layer, tiles: tiles };
        }

        if (select) {
            select.value = layerById(prefs.layer) ? prefs.layer : '';
            select.addEventListener('change', function () { show(select.value); });
        }
        if (opacity) {
            opacity.value = prefs.opacity || 60;
            opacity.addEventListener('input', function () {
                prefs.opacity = Number(opacity.value);
                savePrefs(prefs);
                if (current) current.tiles.setOpacity(prefs.opacity / 100);
            });
        }

        map.on('click', function (e) {
            if (current) queryHazardAt(map, current.layer, e.latlng);
        });

        show(select ? select.value : '');
        syncZona(opts.rumah, opts.onZonaUpdated);

        return { show: show, layers: LAYERS };
    }

    window.SigapInaRisk = { LAYERS: LAYERS, LEVELS: LEVELS, attach: attach };
})(window, document);
