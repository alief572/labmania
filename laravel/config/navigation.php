<?php

// Registry route legacy dan template bootstrap UAT. Struktur sidebar berasal dari ms_menu.
return [
    ['label' => 'Beranda', 'url' => '/', 'menu' => null, 'group' => null],

    ['label' => 'Jenis Produk', 'url' => '/master/jenis-produk', 'menu' => 'master_data.jenis_produk', 'group' => 'Master Data'],
    ['label' => 'Jenis Sertifikat', 'url' => '/master/jenis-sertifikat', 'menu' => 'master_data.jenis_sertifikasi', 'group' => 'Master Data'],
    ['label' => 'Jenis Bidang', 'url' => '/master/jenis-bidang', 'menu' => 'master_data.jenis_bidang', 'group' => 'Master Data'],
    ['label' => 'Varian Pelaksanaan', 'url' => '/master/varian-pelaksanaan', 'menu' => 'master_data.varian_pelaksanaan', 'group' => 'Master Data'],
    ['label' => 'Product', 'url' => '/master/product', 'menu' => 'master_data.paket_produk', 'group' => 'Master Data'],
    ['label' => 'Katalog Produk', 'url' => '/master/katalog', 'menu' => 'master_data.katalog', 'group' => 'Master Data'],

    ['label' => 'Fixed Cost', 'url' => '/master/rate/fixed', 'menu' => 'master_data.rate', 'group' => 'Master Data'],
    ['label' => 'Variable Cost', 'url' => '/master/rate/variable', 'menu' => 'master_data.rate', 'group' => 'Master Data'],
    ['label' => 'Template Keterangan', 'url' => '/master/template-keterangan', 'menu' => 'master_data.template_keterangan', 'group' => 'Master Data'],
    ['label' => 'Template Konfirmasi', 'url' => '/master/template-konfirmasi', 'menu' => 'master_data.template_konfirmasi', 'group' => 'Master Data'],

    ['label' => 'Master Trainer', 'url' => '/master/trainer', 'menu' => 'master_trainer', 'group' => 'Master Data'],
    ['label' => 'Master Peserta', 'url' => '/master/peserta', 'menu' => 'master_data.peserta', 'group' => 'Master Data'],
    ['label' => 'Master Company', 'url' => '/master/company', 'menu' => 'master_data.company', 'group' => 'Master Data'],

    ['label' => 'Master User', 'url' => '/master/user', 'menu' => 'master_data.users', 'group' => 'Master Data'],
    ['label' => 'Master Role', 'url' => '/master/role', 'menu' => 'master_data.role', 'group' => 'Master Data'],

    ['label' => 'Daftar Master Program', 'url' => '/program', 'menu' => 'master_program', 'group' => 'Penjualan Program'],
    ['label' => 'Daftar Penjualan Program', 'url' => '/penjualan-program', 'menu' => 'penjualan_program', 'group' => 'Penjualan Program'],
    ['label' => 'Daftar Quotation', 'url' => '/quotation', 'menu' => 'quotation_program', 'group' => 'Quotation'],
    ['label' => 'Sales Order', 'url' => '/sales-order', 'menu' => 'sales_order', 'group' => 'Quotation'],
    ['label' => 'Refund', 'url' => '/sales-order/refund', 'menu' => 'sales_order_refund', 'group' => 'Quotation'],
    ['label' => 'Invoicing', 'url' => '/invoicing', 'menu' => 'invoicing', 'group' => 'Invoice'],
    ['label' => 'Manage Peserta', 'url' => '/manage-peserta', 'menu' => 'manage_peserta', 'group' => 'Manage Peserta'],
    ['label' => 'Monitoring Peserta', 'url' => '/manage-peserta/monitoring', 'menu' => 'monitoring_peserta', 'group' => 'Manage Peserta'],
    ['label' => 'Pengajuan Biaya', 'url' => '/pengajuan-biaya', 'menu' => 'pengajuan_biaya', 'group' => 'Pengajuan Biaya'],
    ['label' => 'Approval Pengajuan', 'url' => '/pengajuan-biaya/approval', 'menu' => 'approval_pengajuan_biaya', 'group' => 'Pengajuan Biaya'],

    ['label' => 'Role & Permission', 'url' => '/permissions', 'menu' => 'permission_group', 'group' => 'Pengaturan'],
    ['label' => 'Menu Sidebar', 'url' => '/pengaturan/menu', 'menu' => 'menu_labmania', 'group' => 'Pengaturan'],
    ['label' => 'Activity Log', 'url' => '/activity-log', 'menu' => 'activity_log', 'group' => 'Pengaturan'],
];
