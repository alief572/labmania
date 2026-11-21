<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Security-Policy" content="default-src 'none'; style-src 'unsafe-inline'">
    <title>Preview Dokumen Quotation</title>
    {!! $styles !!}
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 16px; background: #e9ecf2; }
        .page { width: 210mm; max-width: 100%; min-height: 297mm; margin: 0 auto 16px; padding: 8mm 15mm 10mm; background: #fff; box-shadow: 0 2px 10px #0000001f; display: flex; flex-direction: column; }
        main { flex: 1; padding-top: 14mm; overflow-wrap: anywhere; }
        table { border-collapse: collapse; }
        .form { width: 100%; }
        .form td:last-child { border-bottom: 1px dotted #999; min-width: 35mm; }
        footer { margin-top: 8mm; }
        @media (max-width: 650px) {
            body { padding: 8px; }
            .page { padding: 16px; min-height: 0; }
            main { padding-top: 24px; }
            table { width: 100%; table-layout: fixed; }
            .form td:last-child { min-width: 0; }
        }
    </style>
</head>
<body>
    @foreach ($pages as $page)
        <section class="page" aria-label="Halaman {{ $loop->iteration }}">
            <header>{!! $header !!}</header>
            <main>{!! $page['body'] !!}</main>
            <footer>{!! $page['footer'] !!}</footer>
        </section>
    @endforeach
</body>
</html>
