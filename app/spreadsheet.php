<?php
declare(strict_types=1);

/**
 * Minimal, dependency-free spreadsheet reader for the channel bulk importer.
 * Supports .csv / .txt (fgetcsv) and .xlsx (ZipArchive + SimpleXML). Returns a
 * list of rows, each row a 0-indexed array of cell strings.
 */
function read_spreadsheet(string $path, string $ext): array
{
    $ext = strtolower($ext);
    if (in_array($ext, ['csv', 'txt'], true)) {
        return read_csv_rows($path);
    }
    if ($ext === 'xlsx') {
        return read_xlsx_rows($path);
    }
    return [];
}

function read_csv_rows(string $path): array
{
    $rows = [];
    if (($h = fopen($path, 'r')) !== false) {
        while (($data = fgetcsv($h, 0, ',')) !== false) {
            // Skip fully empty lines.
            if (count($data) === 1 && trim((string) $data[0]) === '') {
                continue;
            }
            $rows[] = array_map(static fn ($c) => (string) $c, $data);
        }
        fclose($h);
    }
    return $rows;
}

function read_xlsx_rows(string $path): array
{
    $rows = [];
    if (!class_exists('ZipArchive')) {
        return $rows;
    }
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        return $rows;
    }

    // Shared strings table.
    $shared = [];
    if (($ss = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
        $xml = @simplexml_load_string($ss);
        if ($xml) {
            foreach ($xml->si as $si) {
                if (isset($si->t)) {
                    $shared[] = (string) $si->t;
                } else {
                    $t = '';
                    foreach ($si->r as $r) {
                        $t .= (string) $r->t;
                    }
                    $shared[] = $t;
                }
            }
        }
    }

    $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    if ($sheet === false) {
        return $rows;
    }
    $xml = @simplexml_load_string($sheet);
    if (!$xml || !isset($xml->sheetData)) {
        return $rows;
    }

    foreach ($xml->sheetData->row as $row) {
        $cells = [];
        $max = -1;
        foreach ($row->c as $c) {
            $ref = (string) $c['r'];
            $letters = preg_replace('/\d+/', '', $ref);
            $letters = $letters !== '' ? $letters : 'A';
            $idx = 0;
            foreach (str_split($letters) as $ch) {
                $idx = $idx * 26 + (ord(strtoupper($ch)) - 64);
            }
            $idx -= 1;

            $type = (string) $c['t'];
            if ($type === 's') {
                $val = $shared[(int) $c->v] ?? '';
            } elseif ($type === 'inlineStr') {
                $val = (string) $c->is->t;
            } else {
                $val = (string) $c->v;
            }
            $cells[$idx] = $val;
            $max = max($max, $idx);
        }
        $line = [];
        for ($i = 0; $i <= $max; $i++) {
            $line[] = $cells[$i] ?? '';
        }
        // Skip blank rows.
        if (implode('', array_map('trim', $line)) !== '') {
            $rows[] = $line;
        }
    }
    return $rows;
}
