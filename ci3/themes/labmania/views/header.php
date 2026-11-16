<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= !empty($template['title']) && is_string($template['title']) ? htmlspecialchars($template['title']) . ' — ' : ''; ?>Labmania ERP</title>
  
  <!-- Labmania Theme Stylesheet -->
  <link rel="stylesheet" href="<?= base_url('assets/plugins/select2/select2.min.css'); ?>">
  <link rel="stylesheet" href="<?= base_url('assets/labmania/css/style.css'); ?>">
  
  <!-- jQuery (Standard for CI3 AJAX & DOM interaction) -->
  <script src="<?= base_url('assets/plugins/jQuery/jquery-2.2.3.min.js'); ?>"></script>
  <!-- DataTables (daftar tiap modul) + pembungkus Labmania -->
  <script src="<?= base_url('assets/plugins/select2/select2.full.min.js'); ?>"></script>
  <script src="<?= base_url('assets/plugins/datatables/jquery.dataTables.min.js'); ?>"></script>
  <script src="<?= base_url('assets/labmania/js/lm-datatable.js'); ?>"></script>
  <!-- SweetAlert2 (peringatan validasi) -->
  <script src="<?= base_url('assets/plugins/sweetalert/dist/sweetalert2.all.min.js'); ?>"></script>
</head>
<body>
  <div class="app">
