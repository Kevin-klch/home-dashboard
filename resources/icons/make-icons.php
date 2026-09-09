<?php

/**
 * Erzeugt die App-Icons als PNG.
 *
 * Auf diesem Rechner gibt es weder gd noch ImageMagick, deshalb schreibt das
 * Skript die PNG-Struktur selbst: unkomprimierte RGBA-Zeilen, per zlib
 * gepackt, in IHDR/IDAT/IEND verpackt. Kantenglättung über dreifaches
 * Überabtasten.
 */

const SUPERSAMPLE = 3;

function chunk(string $type, string $data): string
{
    return pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
}

/**
 * @param  callable(float, float): array{int, int, int, int}  $shade  liefert RGBA für Punkt in 0..1
 */
function png(int $size, callable $shade): string
{
    $raw = '';
    $step = 1 / ($size * SUPERSAMPLE);

    for ($y = 0; $y < $size; $y++) {
        $raw .= chr(0); // Filter: keiner

        for ($x = 0; $x < $size; $x++) {
            $r = $g = $b = $a = 0;

            for ($sy = 0; $sy < SUPERSAMPLE; $sy++) {
                for ($sx = 0; $sx < SUPERSAMPLE; $sx++) {
                    $u = ($x * SUPERSAMPLE + $sx + 0.5) * $step;
                    $v = ($y * SUPERSAMPLE + $sy + 0.5) * $step;

                    [$pr, $pg, $pb, $pa] = $shade($u, $v);
                    $r += $pr;
                    $g += $pg;
                    $b += $pb;
                    $a += $pa;
                }
            }

            $n = SUPERSAMPLE * SUPERSAMPLE;
            $raw .= chr((int) round($r / $n)).chr((int) round($g / $n))
                .chr((int) round($b / $n)).chr((int) round($a / $n));
        }
    }

    return "\x89PNG\r\n\x1a\n"
        .chunk('IHDR', pack('N2C5', $size, $size, 8, 6, 0, 0, 0))
        .chunk('IDAT', gzcompress($raw, 9))
        .chunk('IEND', '');
}

function inTriangle(float $px, float $py, array $a, array $b, array $c): bool
{
    $sign = fn (array $p, array $q, array $r) => ($p[0] - $r[0]) * ($q[1] - $r[1]) - ($q[0] - $r[0]) * ($p[1] - $r[1]);

    $d1 = $sign([$px, $py], $a, $b);
    $d2 = $sign([$px, $py], $b, $c);
    $d3 = $sign([$px, $py], $c, $a);

    $negative = ($d1 < 0) || ($d2 < 0) || ($d3 < 0);
    $positive = ($d1 > 0) || ($d2 > 0) || ($d3 > 0);

    return ! ($negative && $positive);
}

function inRect(float $x, float $y, float $x1, float $y1, float $x2, float $y2): bool
{
    return $x >= $x1 && $x <= $x2 && $y >= $y1 && $y <= $y2;
}

$shade = function (float $x, float $y): array {
    // Hintergrund: Verlauf von Himmelblau nach Indigo, diagonal.
    $t = max(0.0, min(1.0, ($x + $y) / 2));
    $bg = [
        (int) round(56 + (99 - 56) * $t),     // #38bdf8 -> #6366f1
        (int) round(189 + (102 - 189) * $t),
        (int) round(248 + (241 - 248) * $t),
        255,
    ];

    // Haus: Dach plus Körper, Tür wieder ausgespart.
    $roof = inTriangle($x, $y, [0.50, 0.28], [0.17, 0.53], [0.83, 0.53]);
    $body = inRect($x, $y, 0.28, 0.51, 0.72, 0.76);
    $door = inRect($x, $y, 0.44, 0.60, 0.56, 0.76);

    if (($roof || $body) && ! $door) {
        return [255, 255, 255, 255];
    }

    return $bg;
};

$target = dirname(__DIR__, 2).'/public/icons';

if (! is_dir($target)) {
    mkdir($target, 0755, true);
}

foreach ([180 => 'apple-touch-icon.png', 192 => 'icon-192.png', 512 => 'icon-512.png'] as $size => $file) {
    file_put_contents("{$target}/{$file}", png($size, $shade));
    echo "{$file}: ".number_format(filesize("{$target}/{$file}"))." Bytes".PHP_EOL;
}
