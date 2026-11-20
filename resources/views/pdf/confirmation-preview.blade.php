<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Security-Policy" content="default-src 'none'; style-src 'unsafe-inline'">
    <title>Contoh Formulir Konfirmasi</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 16px; background: #e9ecf2; color: #111; }
        .page { width: 210mm; min-height: 297mm; margin: auto; padding: 8mm 25mm 10mm; background: #fff; box-shadow: 0 2px 10px #0000001f; display: flex; flex-direction: column; font: 11pt/1.3 Calibri, Carlito, Arial, sans-serif; }
        main { flex: 1; padding-top: 16px; overflow-wrap: anywhere; }
        .title { text-align: center; font-size: 14px; font-weight: bold; margin: 4px 0 14px; }
        .form { border-collapse: collapse; margin-bottom: 14px; }
        .form td { padding: 4px 6px 4px 0; vertical-align: top; }
        .form td:first-child { width: 72mm; }
        .form td:last-child { min-width: 70mm; border-bottom: 1px dotted #999; }
        ol { padding-left: 22px; }
        .confirmation-sign { margin: 18px 0 12px auto; width: 75mm; text-align: center !important; }
        ul { padding-left: 20px; text-align: justify; }
        footer { margin-top: 16px; }
        @page { size: A4; margin: 0; }
        @media print {
            body { background: #fff; padding: 0; }
            .page { box-shadow: none; margin: 0; }
        }
    </style>
</head>
<body>
    <section class="page">
        <header>{!! $header !!}</header>
        <main>{!! $body !!}</main>
        <footer>{!! $footer !!}</footer>
    </section>
</body>
</html>
