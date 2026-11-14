<?php

namespace App\Http\Controllers;

use App\Support\Expenses;
use App\Support\Participants;
use App\Support\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PrivateDocumentController extends Controller
{
    public function __invoke(Request $request, string $source, int $id)
    {
        $definition = config('labmania.documents.'.$source);
        abort_unless(is_array($definition), 404);
        $approvalView = $source === 'expense' && ! Gate::allows('menu', ['pengajuan_biaya', 'View']);
        Gate::authorize('menu', [$approvalView ? 'approval_pengajuan_biaya' : $definition['menu'], 'View']);
        $document = DB::table($definition['table'])->where('id', $id)->first();
        abort_unless($document, 404);
        if ($source === 'expense') {
            $header = DB::table('pengajuan_biaya')->where('id', $document->id_pengajuan)->first() ?? abort(404);
            Expenses::access($header->id_penjualan, 'View', $approvalView);
        }
        if ($source === 'bnsp') {
            Participants::person($document->id_quotation_peserta);
        }

        return $this->stream($request, $definition, $document, $id);
    }

    public function salesOrder(Request $request, int $id, string $source, int $documentId)
    {
        Gate::authorize('menu', ['sales_order', 'View']);
        abort_unless(in_array($source, ['quotation', 'confirmation', 'company-confirmation', 'confirmation-history'], true), 404);
        $so = SalesOrder::row($id);
        $definition = config('labmania.documents.'.$source);
        $document = DB::table($definition['table'])->where('id', $documentId)->first() ?? abort(404);
        if ($source === 'confirmation-history') {
            $participant = DB::table('quotation_peserta')->where('id', $document->id_quotation_peserta)->first() ?? abort(404);
            abort_unless((int) $participant->id_quotation === (int) $so->id_quotation && $participant->status === 'deal', 404);
        } else {
            abort_unless((int) $document->id_quotation === (int) $so->id_quotation, 404);
            if ($source === 'confirmation') {
                abort_unless($document->status === 'deal', 404);
            }
        }

        return $this->stream($request, $definition, $document, $documentId);
    }

    private function stream(Request $request, array $definition, object $document, int $id)
    {
        $stored = (string) $document->{$definition['path']};
        abort_if($stored === '', 404);
        $relative = str_replace('\\', '/', ($definition['bare'] ?? false) ? $definition['prefix'].$stored : $stored);
        abort_unless(! str_contains($relative, "\0") && str_starts_with($relative, $definition['prefix']) && ! in_array('..', explode('/', $relative), true), 404);
        $path = false;
        foreach (array_filter([config('labmania.document_root'), config('labmania.legacy_document_root')]) as $root) {
            $base = realpath($root);
            $candidate = $base ? realpath($base.DIRECTORY_SEPARATOR.$relative) : false;
            if ($candidate && str_starts_with($candidate, $base.DIRECTORY_SEPARATOR) && is_file($candidate)) {
                $path = $candidate;
                break;
            }
        }
        abort_unless($path, 404);
        write_log($definition['menu'], 'Unduh', 'Unduh dokumen #'.$id, null, $definition['table'], $id);

        $name = basename((string) $document->{$definition['name']}) ?: 'Dokumen-'.$id;
        if (pathinfo($name, PATHINFO_EXTENSION) === '') {
            $name .= '.'.pathinfo($path, PATHINFO_EXTENSION);
        }

        $headers = ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'no-store, private'];
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        if ($request->boolean('preview') && in_array($mime, ['application/pdf', 'image/png', 'image/jpeg'], true)) {
            return response()->file($path, $headers + ['Content-Type' => $mime, 'Content-Disposition' => 'inline']);
        }

        return response()->download($path, $name, $headers);
    }
}
