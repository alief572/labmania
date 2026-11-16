<?php

namespace App\Support;

class Xlsx
{
    /** ponytail: satu sheet berisi nilai saja; tambah pustaka spreadsheet bila formula/style kompleks dibutuhkan. */
    public static function response(string $name, iterable $rows)
    {
        $path = tempnam(sys_get_temp_dir(), 'lmi-xlsx-');
        if ($path === false) {
            throw new \RuntimeException('Berkas sementara tidak tersedia.');
        }
        try {
            $zip = new \ZipArchive;
            if ($zip->open($path, \ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('XLSX tidak dapat dibuat.');
            }
            try {
                $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
                $zip->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
                $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Data" sheetId="1" r:id="rId1"/></sheets></workbook>');
                $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
                $xml = new \XMLWriter;
                $xml->openMemory();
                $xml->startDocument('1.0', 'UTF-8');
                $xml->startElementNS(null, 'worksheet', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                $xml->startElement('sheetData');
                foreach ($rows as $row) {
                    $xml->startElement('row');
                    foreach ($row as $value) {
                        $xml->startElement('c');
                        $xml->writeAttribute('t', 'inlineStr');
                        $xml->startElement('is');
                        $xml->startElement('t');
                        $xml->writeAttribute('xml:space', 'preserve');
                        $xml->text(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', (string) ($value ?? '')));
                        $xml->endElement();
                        $xml->endElement();
                        $xml->endElement();
                    }
                    $xml->endElement();
                }
                $xml->endElement();
                $xml->endElement();
                $xml->endDocument();
                if (! $zip->addFromString('xl/worksheets/sheet1.xml', $xml->outputMemory())) {
                    throw new \RuntimeException('Isi XLSX gagal disimpan.');
                }
            } finally {
                $zip->close();
            }
            $bytes = file_get_contents($path);
            if ($bytes === false) {
                throw new \RuntimeException('XLSX gagal dibaca.');
            }

            return response($bytes, 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => 'attachment; filename="'.preg_replace('/[^a-zA-Z0-9_-]/', '_', $name).'.xlsx"', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
        } finally {
            unlink($path);
        }
    }
}
