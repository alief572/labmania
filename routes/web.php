<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ConfirmationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\KatalogController;
use App\Http\Controllers\MasterAtributController;
use App\Http\Controllers\MasterProgramController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PesertaController;
use App\Http\Controllers\PrivateDocumentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\RateController;
use App\Http\Controllers\ReconfirmationController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\RescheduleController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\TrainerController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WilayahController;
use App\Http\Middleware\ConfirmationHeaders;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/konfirmasi_kehadiran/{token}', fn (string $token) => redirect('/konfirmasi/'.$token))
    ->where('token', '[a-f0-9]{32}')->middleware(ConfirmationHeaders::class);

Route::middleware(ConfirmationHeaders::class)->prefix('konfirmasi/{token}')->where(['token' => '[a-f0-9]{32}'])->group(function () {
    Route::get('/ulang', [ReconfirmationController::class, 'show']);
    Route::post('/ulang/preview', [ReconfirmationController::class, 'preview'])->middleware('throttle:confirmation');
    Route::post('/ulang', [ReconfirmationController::class, 'store'])->middleware('throttle:confirmation');
    Route::get('/', [ConfirmationController::class, 'show']);
    Route::post('/preview', [ConfirmationController::class, 'preview'])->middleware('throttle:confirmation');
    Route::post('/', [ConfirmationController::class, 'store'])->middleware('throttle:confirmation');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => Inertia::render('Auth/Login'))->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
});
Route::middleware(ConfirmationHeaders::class)->prefix('survey/{token}')->where(['token' => '[A-Za-z0-9_-]{20}'])->group(function () {
    Route::get('/', [SurveyController::class, 'show']);
    Route::post('/', [SurveyController::class, 'store'])->middleware('throttle:confirmation');
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::middleware(['auth', 'verified.account'])->group(function () {
    Route::get('/sales-order/program/{id}/data', [SalesOrderController::class, 'program'])->whereNumber('id')->defaults('datatable', true);
    Route::get('/quotation/batch/{id}/data', [QuotationController::class, 'batch'])->whereNumber('id')->defaults('datatable', true);
    Route::get('/pengaturan/menu/data', [MenuController::class, 'index'])->defaults('datatable', true);
    Route::get('/manage-peserta/{id}/data', [ParticipantController::class, 'show'])->defaults('datatable', true)->whereNumber('id');
    Route::get('/manage-peserta/monitoring/data', [ParticipantController::class, 'monitoring'])->defaults('datatable', true);
    Route::get('/pengajuan-biaya/approval/data', [ExpenseController::class, 'approval'])->defaults('datatable', true);

    Route::get('/pengajuan-biaya/data', [ExpenseController::class, 'index'])->defaults('datatable', true);

    Route::get('/manage-peserta/data', [ParticipantController::class, 'index'])->defaults('datatable', true);

    Route::get('/invoicing/data', [InvoiceController::class, 'index'])->defaults('datatable', true);

    Route::get('/refund/data', [RefundController::class, 'index'])->defaults('datatable', true);
    Route::get('/refund/approval/data', [RefundController::class, 'approval'])->defaults('datatable', true);

    Route::get('/sales-order/data', [SalesOrderController::class, 'index'])->defaults('datatable', true);

    Route::get('/quotation/data', [QuotationController::class, 'index'])->defaults('datatable', true);

    Route::get('/penjualan-program/data', [SalesController::class, 'index'])->defaults('datatable', true);

    Route::get('/program/data', [MasterProgramController::class, 'index'])->defaults('datatable', true);

    Route::prefix('manage-peserta')->group(function () {
        Route::get('/', [ParticipantController::class, 'index']);
        Route::get('/monitoring', [ParticipantController::class, 'monitoring']);
        Route::get('/{id}', [ParticipantController::class, 'show'])->whereNumber('id');
        Route::get('/{id}/excel', [ParticipantController::class, 'excel'])->whereNumber('id');
        Route::get('/{id}/pdf/{kind}', [ParticipantController::class, 'pdf'])->whereNumber('id')->where('kind', 'hadir|name-table|draft');
        Route::post('/{id}/close', [ParticipantController::class, 'close'])->whereNumber('id');
        Route::post('/peserta/{id}/{action}', [ParticipantController::class, 'update'])->whereNumber('id')->where('action', 'nama|draft-kirim|bnsp|catatan|pengiriman|terbit');
        Route::post('/{id}/survey/{action}', [SurveyController::class, 'toggle'])->whereNumber('id')->where('action', 'buka|tutup');
        Route::get('/{id}/survey/excel', [SurveyController::class, 'excel'])->whereNumber('id');
    });
    Route::prefix('pengajuan-biaya')->group(function () {
        Route::get('/', [ExpenseController::class, 'index']);
        Route::get('/approval', [ExpenseController::class, 'approval']);
        Route::get('/program/{id}/data', [ExpenseController::class, 'program'])->whereNumber('id')->defaults('datatable', true);
        Route::get('/program/{id}', [ExpenseController::class, 'program'])->whereNumber('id');
        Route::post('/program/{id}/{kind}', [ExpenseController::class, 'store'])->whereNumber('id')->where('kind', 'pengajuan|overbudget');
        Route::get('/{kind}/{id}', [ExpenseController::class, 'show'])->whereNumber('id')->where('kind', 'pengajuan|overbudget');
        Route::post('/{kind}/{id}/{action}', [ExpenseController::class, 'process'])->whereNumber('id')->where('kind', 'pengajuan|overbudget')->where('action', 'setujui|tolak|batal|paid');
    });
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard/kpi/{kpi}/data', [DashboardController::class, 'data'])->where('kpi', 'final|pend|prof|draft')->defaults('datatable', true);
    Route::get('/dashboard/kpi/{kpi}', [DashboardController::class, 'detail'])->where('kpi', 'final|pend|prof|draft');
    Route::get('/activity-log/data', ActivityLogController::class)->defaults('datatable', true);
    Route::get('/activity-log', ActivityLogController::class)->name('activity-log');
    Route::prefix('pengaturan/menu')->group(function () {
        Route::get('/', [MenuController::class, 'index']);
        Route::post('/', [MenuController::class, 'store']);
        Route::put('/{id}', [MenuController::class, 'update'])->whereNumber('id');
        Route::post('/{id}/toggle', [MenuController::class, 'toggle'])->whereNumber('id');
        Route::post('/{id}/pindah/{dir}', [MenuController::class, 'move'])->whereNumber('id')->where('dir', 'up|down');
    });
    Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions');
    Route::get('/permissions/data', [PermissionController::class, 'index'])->defaults('datatable', true);
    Route::get('/permissions/roles/{role}', [PermissionController::class, 'role'])->whereNumber('role');
    Route::put('/permissions/roles/{role}', [PermissionController::class, 'roleUpdate'])->whereNumber('role');
    Route::get('/permissions/users/{user}', [PermissionController::class, 'user'])->whereNumber('user');
    Route::put('/permissions/users/{user}', [PermissionController::class, 'userUpdate'])->whereNumber('user');
    Route::get('/documents/{source}/{id}', PrivateDocumentController::class)->whereNumber('id');

    Route::prefix('penjualan-program')->group(function () {
        Route::get('/{id}/reschedule', [RescheduleController::class, 'show'])->whereNumber('id');
        Route::post('/{id}/reschedule', [RescheduleController::class, 'store'])->whereNumber('id');
        Route::get('/{id}/reschedule/{history}/pdf', [RescheduleController::class, 'historyPdf'])->whereNumber(['id', 'history']);
        Route::get('/', [SalesController::class, 'index']);
        Route::get('/mulai', [SalesController::class, 'start']);
        Route::get('/create', [SalesController::class, 'create']);
        Route::post('/', [SalesController::class, 'store']);
        Route::get('/{id}', [SalesController::class, 'show'])->whereNumber('id');
        Route::get('/{id}/pdf', [SalesController::class, 'pdf'])->whereNumber('id');
        Route::put('/{id}', [SalesController::class, 'update'])->whereNumber('id');
        Route::post('/{id}/{action}', [SalesController::class, 'status'])->whereNumber('id')->whereIn('action', ['finalkan', 'batalkan']);
    });
    Route::prefix('quotation')->group(function () {
        Route::get('/{id}/revisi/{revision}/{kind}', [RescheduleController::class, 'revision'])->whereNumber(['id', 'revision'])->whereIn('kind', ['quotation', 'so']);
        Route::post('/peserta/{id}/konfirmasi-ulang', [ReconfirmationController::class, 'process'])->whereNumber('id');
        Route::get('/', [QuotationController::class, 'index']);
        Route::get('/create', [QuotationController::class, 'create']);
        Route::post('/preview', [QuotationController::class, 'preview']);
        Route::post('/', [QuotationController::class, 'store']);
        Route::get('/batch/{id}', [QuotationController::class, 'batch'])->whereNumber('id');
        Route::get('/batch/{id}/pdf', [QuotationController::class, 'batchPdf'])->whereNumber('id');
        Route::get('/{id}', [QuotationController::class, 'show'])->whereNumber('id');
        Route::put('/{id}', [QuotationController::class, 'update'])->whereNumber('id');
        Route::get('/{id}/pdf', [QuotationController::class, 'pdf'])->whereNumber('id');
        Route::get('/{id}/letters', [QuotationController::class, 'letters'])->whereNumber('id');
        Route::get('/{id}/excel', [QuotationController::class, 'excel'])->whereNumber('id');
        Route::post('/{id}/kirim', [QuotationController::class, 'send'])->whereNumber('id');
        Route::post('/{id}/batalkan', [QuotationController::class, 'cancel'])->whereNumber('id');
        Route::post('/{id}/kuota', [QuotationController::class, 'quota'])->whereNumber('id');
        Route::post('/{id}/deal', [QuotationController::class, 'deal'])->whereNumber('id');
        Route::post('/{id}/dokumen', [QuotationController::class, 'documents'])->whereNumber('id');
        Route::post('/peserta/{id}/surat', [QuotationController::class, 'replaceLetter'])->whereNumber('id');
    });

    Route::prefix('refund')->group(function () {
        Route::get('/', [RefundController::class, 'index']);
        Route::get('/approval', [RefundController::class, 'approval']);
        Route::get('/{id}', [RefundController::class, 'show'])->whereNumber('id');
        Route::post('/{id}', [RefundController::class, 'process'])->whereNumber('id');
        Route::post('/{id}/keputusan', [RefundController::class, 'decide'])->whereNumber('id');
        Route::post('/{id}/ajukan-ulang', [RefundController::class, 'resubmit'])->whereNumber('id');
    });

    Route::prefix('sales-order')->group(function () {
        // Tab Refund di Sales Order dihapus (RF-FR-01); alamat lama dialihkan agar tautan dan penanda buku tidak patah.
        Route::get('/refund', fn () => redirect('/refund', 301));
        Route::get('/refund/{id}', fn (int $id) => redirect('/refund/'.$id, 301))->whereNumber('id');
        Route::get('/', [SalesOrderController::class, 'index']);
        Route::get('/program/{id}', [SalesOrderController::class, 'program'])->whereNumber('id');
        Route::get('/{id}', [SalesOrderController::class, 'show'])->whereNumber('id');
        Route::get('/{id}/pdf', [SalesOrderController::class, 'pdf'])->whereNumber('id');
        Route::get('/{id}/letters', [SalesOrderController::class, 'letters'])->whereNumber('id');
        Route::get('/{id}/documents/{source}/{documentId}', [PrivateDocumentController::class, 'salesOrder'])->whereNumber(['id', 'documentId']);
        Route::post('/{id}/siap-invoice', [SalesOrderController::class, 'ready'])->whereNumber('id');
    });
    Route::get('/surat-kerja-trainer/{id}', [SalesOrderController::class, 'assignment'])->whereNumber('id');
    Route::get('/surat-kerja-trainer/{id}/pdf', [SalesOrderController::class, 'assignmentPdf'])->whereNumber('id');
    Route::prefix('invoicing')->group(function () {
        Route::get('/', [InvoiceController::class, 'index']);
        Route::get('/create', [InvoiceController::class, 'create']);
        Route::get('/excel', [InvoiceController::class, 'excel']);
        Route::post('/', [InvoiceController::class, 'store']);
        Route::get('/{id}', [InvoiceController::class, 'show'])->whereNumber('id');
        Route::get('/{id}/pdf', [InvoiceController::class, 'pdf'])->whereNumber('id');
        Route::post('/{id}/pembayaran', [InvoiceController::class, 'payment'])->whereNumber('id');
        Route::post('/{id}/bukti-setor', [InvoiceController::class, 'withholding'])->whereNumber('id');
    });

    Route::prefix('program')->group(function () {
        Route::get('/', [MasterProgramController::class, 'index'])->name('master-program.index');
        Route::get('/create', [MasterProgramController::class, 'create']);
        Route::post('/', [MasterProgramController::class, 'store']);
        Route::get('/{id}', [MasterProgramController::class, 'show'])->whereNumber('id')->name('master-program.show');
        Route::put('/{id}', [MasterProgramController::class, 'update'])->whereNumber('id');
        Route::get('/{id}/ringkasan', [MasterProgramController::class, 'ringkasan'])->whereNumber('id');
        Route::post('/{id}/finalkan', [MasterProgramController::class, 'finalkan'])->whereNumber('id');
        Route::post('/{id}/batalkan', [MasterProgramController::class, 'batalkan'])->whereNumber('id');
    });

    Route::prefix('master')->group(function () {
        $atribut = 'jenis-produk|jenis-sertifikat|jenis-bidang|varian-pelaksanaan';
        Route::get('/{atribut}/data', [MasterAtributController::class, 'index'])->where('atribut', $atribut)->defaults('datatable', true);
        Route::get('/{atribut}', [MasterAtributController::class, 'index'])->where('atribut', $atribut);
        Route::post('/{atribut}', [MasterAtributController::class, 'store'])->where('atribut', $atribut);
        Route::put('/{atribut}/{id}', [MasterAtributController::class, 'update'])->where('atribut', $atribut)->whereNumber('id');
        Route::post('/{atribut}/{id}/toggle', [MasterAtributController::class, 'toggle'])->where('atribut', $atribut)->whereNumber('id');

        Route::get('/product/data', [ProductController::class, 'index'])->defaults('datatable', true);
        Route::get('/product', [ProductController::class, 'index']);
        Route::get('/product/create', [ProductController::class, 'create']);
        Route::post('/product', [ProductController::class, 'store']);
        Route::get('/product/{id}', [ProductController::class, 'detail'])->whereNumber('id');
        Route::put('/product/{id}', [ProductController::class, 'update'])->whereNumber('id');
        Route::post('/product/{id}/toggle', [ProductController::class, 'toggle'])->whereNumber('id');
        Route::put('/product/{id}/lingkup', [ProductController::class, 'lingkup'])->whereNumber('id');

        Route::prefix('rate/{tipe}')->where(['tipe' => 'fixed|variable'])->group(function () {
            Route::get('/data', [RateController::class, 'index'])->defaults('datatable', true);
            Route::get('/', [RateController::class, 'index']);
            Route::post('/', [RateController::class, 'store']);
            Route::put('/{id}', [RateController::class, 'update'])->whereNumber('id');
            Route::post('/{id}/toggle', [RateController::class, 'toggle'])->whereNumber('id');
        });

        Route::get('/template-keterangan/data', [TemplateController::class, 'keterangan'])->defaults('datatable', true);
        Route::get('/template-keterangan', [TemplateController::class, 'keterangan']);
        Route::post('/template-keterangan', [TemplateController::class, 'storeKeterangan']);
        Route::put('/template-keterangan/{id}', [TemplateController::class, 'updateKeterangan'])->whereNumber('id');
        Route::post('/template-keterangan/{id}/toggle', [TemplateController::class, 'toggleKeterangan'])->whereNumber('id');
        Route::get('/template-konfirmasi/data', [TemplateController::class, 'konfirmasi'])->defaults('datatable', true);
        Route::get('/template-konfirmasi', [TemplateController::class, 'konfirmasi']);
        Route::post('/template-konfirmasi', [TemplateController::class, 'storeKonfirmasi']);
        Route::get('/template-konfirmasi/{id}/contoh', [TemplateController::class, 'contoh'])->whereNumber('id');
        Route::put('/template-konfirmasi/{id}', [TemplateController::class, 'updateKonfirmasi'])->whereNumber('id');
        Route::post('/template-konfirmasi/{id}/toggle', [TemplateController::class, 'toggleKonfirmasi'])->whereNumber('id');

        Route::get('/user/data', [UserController::class, 'index'])->defaults('datatable', true);
        Route::get('/user', [UserController::class, 'index']);
        Route::post('/user', [UserController::class, 'store']);
        Route::put('/user/{id}', [UserController::class, 'update'])->whereNumber('id');
        Route::post('/user/{id}/toggle', [UserController::class, 'toggle'])->whereNumber('id');
        Route::post('/user/{id}/reset-password', [UserController::class, 'resetPassword'])->whereNumber('id');

        Route::get('/role/data', [RoleController::class, 'index'])->defaults('datatable', true);
        Route::get('/role', [RoleController::class, 'index']);
        Route::post('/role', [RoleController::class, 'store']);
        Route::put('/role/{id}', [RoleController::class, 'update'])->whereNumber('id');
        Route::delete('/role/{id}', [RoleController::class, 'destroy'])->whereNumber('id');

        Route::get('/trainer/data', [TrainerController::class, 'index'])->defaults('datatable', true);
        Route::get('/trainer', [TrainerController::class, 'index']);
        Route::post('/trainer', [TrainerController::class, 'store']);
        Route::get('/trainer/{id}', [TrainerController::class, 'show'])->whereNumber('id');
        Route::get('/trainer/{id}/cv.pdf', [TrainerController::class, 'cvPdf'])->whereNumber('id');
        Route::post('/trainer/{id}', [TrainerController::class, 'update'])->whereNumber('id');
        Route::post('/trainer/{id}/toggle', [TrainerController::class, 'toggle'])->whereNumber('id');

        Route::get('/peserta/data', [PesertaController::class, 'index'])->defaults('datatable', true);
        Route::get('/peserta', [PesertaController::class, 'index']);
        Route::post('/peserta', [PesertaController::class, 'store']);
        Route::put('/peserta/{id}', [PesertaController::class, 'update'])->whereNumber('id');
        Route::delete('/peserta/{id}', [PesertaController::class, 'destroy'])->whereNumber('id');

        Route::get('/company/data', [CompanyController::class, 'index'])->defaults('datatable', true);
        Route::get('/company', [CompanyController::class, 'index']);
        Route::post('/company', [CompanyController::class, 'store']);
        Route::put('/company/{id}', [CompanyController::class, 'update'])->where('id', 'CO-\d+');
        Route::post('/company/{id}/toggle', [CompanyController::class, 'toggle'])->where('id', 'CO-\d+');
        Route::delete('/company/{id}', [CompanyController::class, 'destroy'])->where('id', 'CO-\d+');

        Route::get('/wilayah/provinces', [WilayahController::class, 'provinces']);
        Route::get('/wilayah/regencies/{code}', [WilayahController::class, 'regencies']);

        Route::get('/katalog', [KatalogController::class, 'index']);
        Route::get('/katalog/pdf', [KatalogController::class, 'pdf']);
    });
});
