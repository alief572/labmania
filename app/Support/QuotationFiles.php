<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class QuotationFiles
{
    public static function store(UploadedFile $file, bool $customer = false, ?string $prefix = null): array
    {
        validator(['file' => $file], ['file' => ['required', 'file', 'max:5120', $customer ? 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx' : 'mimes:pdf,jpg,jpeg,png']])->validate();
        $ext = strtolower($file->getClientOriginalExtension());
        $ext = $ext === 'jpeg' ? 'jpg' : $ext;
        if (! in_array($ext, $customer ? ['pdf', 'jpg', 'png', 'doc', 'docx', 'xls', 'xlsx'] : ['pdf', 'jpg', 'png'], true)) {
            throw ValidationException::withMessages(['file' => 'Ekstensi berkas tidak didukung.']);
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath());
        $allowed = ['pdf' => ['application/pdf'], 'jpg' => ['image/jpeg'], 'png' => ['image/png'],
            'doc' => ['application/msword', 'application/CDFV2', 'application/x-ole-storage'],
            'xls' => ['application/vnd.ms-excel', 'application/CDFV2', 'application/x-ole-storage'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip']];
        if (! in_array($mime, $allowed[$ext], true)) {
            throw ValidationException::withMessages(['file' => 'Isi berkas tidak sesuai ekstensi.']);
        }
        if (in_array($ext, ['jpg', 'png'], true)) {
            self::validateImage($file->getRealPath());
        }
        if (in_array($ext, ['docx', 'xlsx'], true)) {
            $zip = new \ZipArchive;
            if ($zip->open($file->getRealPath()) !== true) {
                throw ValidationException::withMessages(['file' => 'Dokumen Office rusak.']);
            }
            try {
                $valid = $zip->locateName('[Content_Types].xml') !== false && $zip->locateName($ext === 'docx' ? 'word/document.xml' : 'xl/workbook.xml') !== false;
            } finally {
                $zip->close();
            }
            if (! $valid) {
                throw ValidationException::withMessages(['file' => 'Dokumen Office tidak sesuai format.']);
            }
        }
        $prefix ??= $customer ? 'uploads/quotation_dokumen/' : 'uploads/konfirmasi_kehadiran/';
        abort_unless(in_array($prefix, ['uploads/quotation_dokumen/', 'uploads/konfirmasi_kehadiran/', 'uploads/penjualan_program_reschedule/', 'uploads/refund/', 'uploads/pengajuan_biaya/', 'uploads/sertifikat/'], true), 500);
        $path = $prefix.bin2hex(random_bytes(16)).'.'.$ext;
        Storage::build(['driver' => 'local', 'root' => config('labmania.document_root'), 'throw' => true])->put($path, file_get_contents($file->getRealPath()));

        return ['path' => $path, 'name' => mb_substr(basename(str_replace('\\', '/', $file->getClientOriginalName())), 0, 150), 'ext' => $ext, 'size' => $file->getSize()];
    }

    public static function validateImage(string $path): void
    {
        $size = @getimagesize($path);
        // ponytail: batasi decode ke 16 megapiksel; foto lebih besar perlu diperkecil atau disimpan sebagai PDF.
        if (! $size || $size[0] * $size[1] > 16000000) {
            throw ValidationException::withMessages(['file' => 'Gambar rusak atau melebihi 16 megapiksel.']);
        }
        $image = @imagecreatefromstring(file_get_contents($path));
        if ($image === false) {
            throw ValidationException::withMessages(['file' => 'Gambar tidak dapat dibaca.']);
        }
    }

    public static function discard(array $files): void
    {
        $disk = Storage::build(['driver' => 'local', 'root' => config('labmania.document_root'), 'throw' => true]);
        foreach ($files as $file) {
            $disk->delete($file['path']);
        }
    }

    public static function path(string $relative): string
    {
        abort_unless(str_starts_with($relative, 'uploads/konfirmasi_kehadiran/'), 404);

        return self::storedPath($relative);
    }

    public static function storedPath(string $relative): string
    {
        abort_if(str_contains($relative, "\0") || str_contains($relative, '\\') || in_array('..', explode('/', $relative), true) || ! preg_match('#^uploads/(konfirmasi_kehadiran|quotation_dokumen|penjualan_program_reschedule|refund|pengajuan_biaya|sertifikat)/#', $relative), 404);
        foreach (array_filter([config('labmania.document_root'), config('labmania.legacy_document_root')]) as $root) {
            $base = realpath($root);
            $path = $base ? realpath($base.DIRECTORY_SEPARATOR.$relative) : false;
            if ($path && str_starts_with($path, $base.DIRECTORY_SEPARATOR) && is_file($path)) {
                return $path;
            }
        }
        abort(404, 'Surat konfirmasi tidak ditemukan.');
    }
}
