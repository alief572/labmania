<?php

return [
    'coa_connection' => env('COA_DB_CONNECTION', 'gl'),
    'local_uat_enabled' => env('LOCAL_UAT_ENABLED', false),
    'operasional' => [
        // ponytail: aktifkan hanya setelah pertanyaan dan aturan publik disahkan BA; tidak menerima toggle dari UI/env.
        'survey_public_enabled' => false,
        'font_dir' => env('PARTICIPANT_FONT_DIR', 'C:/Windows/Fonts'),
    ],
    'invoice' => [
        'coa' => [
            'piutang' => ['id' => env('INVOICE_COA_PIUTANG_ID'), 'number' => '1102-01-01'],
            'ppn_dn' => ['id' => env('INVOICE_COA_PPN_DN_ID'), 'number' => '2104-01-07'],
            'public' => ['id' => env('INVOICE_COA_PUBLIC_ID'), 'number' => '4101-01-03'],
            'b2b' => ['id' => env('INVOICE_COA_B2B_ID'), 'number' => '4101-01-02'],
            'uang_muka_wapu' => ['id' => env('INVOICE_COA_UANG_MUKA_WAPU_ID'), 'number' => null],
            'hutang_wapu' => ['id' => env('INVOICE_COA_HUTANG_WAPU_ID'), 'number' => null],
        ],
        'print' => [
            'bank' => env('INVOICE_BANK'), 'rekening' => env('INVOICE_REKENING'),
            'atas_nama' => env('INVOICE_ATAS_NAMA'), 'penanda_tangan' => env('INVOICE_PENANDA_TANGAN'),
            'jabatan' => env('INVOICE_JABATAN'), 'kota' => env('INVOICE_KOTA', 'Bekasi'),
            'kode_dokumen' => env('INVOICE_KODE_DOKUMEN', 'FR/LMI-FN-01/01'),
        ],
    ],
    'audit_retention_enabled' => env('AUDIT_RETENTION_ENABLED', false),
    'activity_log_role_ids' => array_values(array_filter(array_map('intval', explode(',', (string) env('ACTIVITY_LOG_ROLE_IDS', ''))), fn ($id) => $id > 0)),
    'legacy_document_root' => env('LEGACY_DOCUMENT_ROOT'),
    'document_root' => storage_path('app/private/documents'),
    'documents' => [
        'expense' => ['table' => 'pengajuan_biaya_lampiran', 'path' => 'file_path', 'name' => 'nama_file', 'menu' => 'pengajuan_biaya', 'prefix' => 'uploads/pengajuan_biaya/'],
        'bnsp' => ['table' => 'quotation_peserta_sertifikat', 'path' => 'file_sertifikat_bnsp', 'name' => 'no_sertifikat_bnsp', 'menu' => 'manage_peserta', 'prefix' => 'uploads/sertifikat/'],
        'refund' => ['table' => 'sales_order_refund', 'path' => 'bukti_transfer', 'name' => 'bukti_transfer_nama', 'menu' => 'sales_order_refund', 'prefix' => 'uploads/refund/'],
        'confirmation' => ['table' => 'quotation_peserta', 'path' => 'surat_konfirmasi', 'name' => 'surat_konfirmasi_nama', 'menu' => 'quotation_program', 'prefix' => 'uploads/konfirmasi_kehadiran/'],
        'company-confirmation' => ['table' => 'quotation_konfirmasi_company', 'path' => 'surat_konfirmasi', 'name' => 'surat_konfirmasi_nama', 'menu' => 'quotation_program', 'prefix' => 'uploads/konfirmasi_kehadiran/'],
        'confirmation-history' => ['table' => 'quotation_peserta_surat_riwayat', 'path' => 'surat_konfirmasi', 'name' => 'surat_konfirmasi_nama', 'menu' => 'quotation_program', 'prefix' => 'uploads/konfirmasi_kehadiran/'],
        'quotation' => ['table' => 'quotation_dokumen', 'path' => 'path_file', 'name' => 'nama_file', 'menu' => 'quotation_program', 'prefix' => 'uploads/quotation_dokumen/'],
        'reschedule' => ['table' => 'penjualan_program_reschedule_evidence', 'path' => 'path_file', 'name' => 'nama_file', 'menu' => 'penjualan_program', 'prefix' => 'uploads/penjualan_program_reschedule/'],
        // Lampiran trainer: kolom file_path hanya menyimpan nama berkas acak ('bare'), folder = prefix.
        'trainer' => ['table' => 'ms_trainer_lampiran', 'path' => 'file_path', 'name' => 'dokumen', 'menu' => 'master_trainer', 'prefix' => 'uploads/trainers/', 'bare' => true],
    ],

    // Master Data fase 2. Prefix, digit, dan batas di bawah masih [USULAN] BA (Q-01, Q-38); ubah di sini bila BA menetapkan lain.
    'atribut' => [
        'jenis-produk' => ['table' => 'ms_jenis_produk', 'prefix' => 'JP', 'label' => 'Jenis Produk', 'menu' => 'master_data.jenis_produk'],
        'jenis-sertifikat' => ['table' => 'ms_jenis_sertifikasi', 'prefix' => 'JS', 'label' => 'Jenis Sertifikat', 'menu' => 'master_data.jenis_sertifikasi'],
        'jenis-bidang' => ['table' => 'ms_jenis_bidang', 'prefix' => 'JB', 'label' => 'Jenis Bidang', 'menu' => 'master_data.jenis_bidang'],
        'varian-pelaksanaan' => ['table' => 'ms_varian_pelaksanaan', 'prefix' => 'VP', 'label' => 'Varian Pelaksanaan', 'menu' => 'master_data.varian_pelaksanaan'],
    ],
    'code_prefix' => ['product' => 'PC', 'peserta' => 'PS', 'company' => 'CO', 'rate_fixed' => 'FC', 'rate_variable' => 'VC', 'template_keterangan' => 'TK', 'template_konfirmasi' => 'KF', 'trainer' => 'TR'],
    'lingkup' => ['max_items' => 20, 'max_length' => 200],
    // Pilihan Master User: daftar contoh (Q-25 belum final); isi final menyusul dari data Labmania.
    'user_options' => ['gender' => ['Laki-laki', 'Perempuan'], 'agama' => ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'], 'tipe' => ['Tetap', 'Kontrak', 'Magang', 'Freelance']],
    'jenis_instansi' => ['Swasta', 'BUMN/BUMD', 'Pemerintah', 'Pendidikan', 'Lainnya'],
];
