<?php
// core/Supplier/XlsxImageExtractor.php
//
// Extracts embedded images from an .xlsx file and maps them to 1-based
// Excel row numbers by parsing drawing anchors.
//
// HOCO supplier sheets store product photos as embedded drawings anchored
// to the product's row (Picture/Package columns), not as cell values, so
// ProductCsvImporter::parseXlsx() cannot see them. This extractor unzips
// xl/media/* and resolves each <xdr:twoCellAnchor>/<xdr:oneCellAnchor>
// <xdr:from><xdr:row> + <a:blip r:embed> pair via the drawing rels.

class XlsxImageExtractor {
    /**
     * @return array<int, string[]> 1-based excel row => list of saved relative paths
     */
    public static function extract(string $xlsxPath, string $destDir, string $prefix = 'hoco'): array {
        if (!is_dir($destDir)) @mkdir($destDir, 0755, true);
        $zip = new ZipArchive();
        if ($zip->open($xlsxPath) !== true) return [];

        // 1. Collect media files.
        $media = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_starts_with($name, 'xl/media/')) $media[] = $name;
        }
        if (!$media) { $zip->close(); return []; }

        // 2. For each drawing, parse anchors + rels.
        $byRow = [];
        $fileSeq = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!preg_match('#^xl/drawings/drawing(\d*)\.xml$#', $name, $m)) continue;
            $xml = $zip->getFromName($name);
            if ($xml === false) continue;
            $num = $m[1] !== '' ? $m[1] : '1';
            $relsName = "xl/drawings/_rels/drawing{$num}.xml.rels";
            $relsXml = $zip->getFromName($relsName);
            $embedToMedia = [];
            if ($relsXml !== false) {
                // <Relationship Id="rId1" Target="../media/image1.jpeg"/>
                if (preg_match_all('/Id="([^"]+)"[^>]*Target="([^"]+)"/', $relsXml, $rm, PREG_SET_ORDER)) {
                    foreach ($rm as $rel) {
                        $target = $rel[2];
                        $base = basename($target);
                        foreach ($media as $med) {
                            if (basename($med) === $base) { $embedToMedia[$rel[1]] = $med; break; }
                        }
                    }
                }
            }
            // Each anchor block: grab <xdr:from> row (0-based) + first r:embed in block.
            // Split on anchor opens to avoid cross-anchor matching.
            $parts = preg_split('/<xdr:(?:twoCellAnchor|oneCellAnchor|absoluteAnchor)[ >]/', $xml);
            array_shift($parts);
            foreach ($parts as $block) {
                if (!preg_match('/<xdr:from>.*?<xdr:row>(\d+)<\/xdr:row>/s', $block, $rowM)) continue;
                if (!preg_match('/r:embed="(rId\d+)"/', $block, $embM)) continue;
                $excelRow = (int)$rowM[1] + 1; // 0-based -> 1-based
                $med = $embedToMedia[$embM[1]] ?? null;
                if ($med === null) {
                    // Fallback: nth image in file order.
                    continue;
                }
                $data = $zip->getFromName($med);
                if ($data === false || $data === '') continue;
                $ext = strtolower(pathinfo($med, PATHINFO_EXTENSION));
                $ext = match ($ext) {
                    'jpg', 'jpeg' => 'jpg',
                    'png' => 'png',
                    'gif' => 'gif',
                    'webp' => 'webp',
                    'bmp' => 'jpg',
                    default => 'jpg',
                };
                $seq = ($fileSeq[$excelRow] ?? 0) + 1;
                $fileSeq[$excelRow] = $seq;
                $fname = sprintf('%s-r%d-%d.%s', $prefix, $excelRow, $seq, $ext);
                @file_put_contents($destDir . '/' . $fname, $data);
                // Store path in the same relative form the importer validates.
                $byRow[$excelRow][] = 'public/uploads/products/' . $fname;
            }
        }
        $zip->close();

        // Fallback: if anchor parsing yielded nothing (unusual drawing style),
        // distribute media files in row order is NOT safe — return empty so the
        // caller keeps gallery-link placeholders instead of mismatched photos.
        return $byRow;
    }
}
