<?php

/*
|--------------------------------------------------------------------------
| InaRISK BNPB
|--------------------------------------------------------------------------
| Layer bahaya dari https://gis.bnpb.go.id (seluruh Indonesia). Setiap
| bahaya punya dua layanan ImageServer:
|   - 'tampilan' : layer_bahaya_* berwarna, untuk overlay peta
|   - 'indeks'   : INDEKS_BAHAYA_* bernilai 0–1, untuk membaca tingkat
|                  bahaya di satu titik (identify)
*/

return [

    'base_url' => env('INARISK_BASE_URL', 'https://gis.bnpb.go.id/server/rest/services/inarisk'),

    // Server InaRISK lambat (bisa ±30–60 detik per permintaan).
    'timeout' => (int) env('INARISK_TIMEOUT', 50),

    // Layer yang menentukan zona Merah/Kuning/Hijau rumah.
    // Ganti ke 'tsunami' bila zona hanya ingin berdasarkan bahaya tsunami.
    'zona_layer' => env('INARISK_ZONA_LAYER', 'multi'),

    // Batas tingkat bahaya InaRISK (indeks 0–1).
    'batas_sedang' => 0.333,
    'batas_tinggi' => 0.666,

    'layers' => [
        'banjir'           => ['label' => 'Banjir',             'tampilan' => 'layer_bahaya_banjir',                       'indeks' => 'INDEKS_BAHAYA_BANJIR'],
        'tanah_longsor'    => ['label' => 'Tanah longsor',      'tampilan' => 'layer_bahaya_tanah_longsor',                'indeks' => 'INDEKS_BAHAYA_TANAHLONGSOR'],
        'gempabumi'        => ['label' => 'Gempa bumi',         'tampilan' => 'layer_bahaya_gempabumi',                    'indeks' => 'INDEKS_BAHAYA_GEMPABUMI'],
        'tsunami'          => ['label' => 'Tsunami',            'tampilan' => 'layer_bahaya_tsunami',                      'indeks' => 'INDEKS_BAHAYA_TSUNAMI'],
        'banjir_bandang'   => ['label' => 'Banjir bandang',     'tampilan' => 'layer_bahaya_banjir_bandang',               'indeks' => 'INDEKS_BAHAYA_BANJIRBANDANG'],
        'cuaca_ekstrim'    => ['label' => 'Cuaca ekstrem',      'tampilan' => 'layer_bahaya_cuaca_ekstrim',                'indeks' => 'INDEKS_BAHAYA_CUACAEKSTRIM'],
        'gelombang_abrasi' => ['label' => 'Gelombang & abrasi', 'tampilan' => 'layer_bahaya_gelombang_ekstrim_dan_abrasi', 'indeks' => 'INDEKS_BAHAYA_GEA'],
        'kekeringan'       => ['label' => 'Kekeringan',         'tampilan' => 'layer_bahaya_kekeringan',                   'indeks' => 'INDEKS_BAHAYA_KEKERINGAN'],
        'karhutla'         => ['label' => 'Kebakaran hutan',    'tampilan' => 'layer_bahaya_kebakaran_hutan_dan_lahan',    'indeks' => 'INDEKS_BAHAYA_KARHUTLA'],
        'gunung_api'       => ['label' => 'Gunung api',         'tampilan' => 'layer_bahaya_letusan_gunungapi',            'indeks' => 'INDEKS_BAHAYA_GUNUNGAPI'],
        // Likuefaksi tidak punya layer berwarna → indeks diwarnai kuning-merah lewat renderingRule (lihat public/js/sigap-inarisk.js).
        'likuefaksi'       => ['label' => 'Likuefaksi',         'tampilan' => 'INDEKS_BAHAYA_LIKUEFAKSI',                  'indeks' => 'INDEKS_BAHAYA_LIKUEFAKSI'],
        'multi'            => ['label' => 'Multi bahaya',       'tampilan' => 'layer_bahaya_multi',                        'indeks' => 'INDEKS_MULTI_BAHAYA'],
    ],

];
