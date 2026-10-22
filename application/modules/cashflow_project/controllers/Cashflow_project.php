<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/*
 * @author Harboens
 * @copyright Copyright (c) 2020
 *
 * This is controller for Pengajuan Rutin
 */

class Cashflow_project extends Admin_Controller
{
    //Permission
    protected $viewPermission     = 'Cashflow_Project.View';
    protected $addPermission      = 'Cashflow_Project.Add';
    protected $managePermission = 'Cashflow_Project.Manage';
    protected $deletePermission = 'Cashflow_Project.Delete';

    public function __construct()
    {
        parent::__construct();
        $this->template->title('Cashflow Project');
        $this->template->page_icon('fa fa-cubes');
        $this->load->library('upload');
        $this->load->model(array('Cashflow_project/Cashflow_project_model'));
        date_default_timezone_set('Asia/Bangkok');
    }

    // View Page Function

    public function index()
    {
        $this->auth->restrict($this->viewPermission);
        $this->template->title('Laporan Realisasi Budget Project');

        $years = $this->Cashflow_project_model->get_available_years();

        // Determine default year: current year if available, otherwise max year
        $current_year = (int) date('Y');
        if (in_array($current_year, $years)) {
            $default_year = $current_year;
        } else {
            $default_year = !empty($years) ? $years[0] : $current_year; // years[0] is max since sorted DESC
        }

        $data = [
            'years' => $years,
            'default_year' => $default_year
        ];

        $this->template->set($data);
        $this->template->render('index');
    }

    public function view_cashflow($id_spk_budgeting)
    {
        $this->auth->restrict($this->viewPermission);

        $id_spk_budgeting = urldecode($id_spk_budgeting);
        $id_spk_budgeting = str_replace('|', '/', $id_spk_budgeting);

        // Get SPK header info
        $header = $this->Cashflow_project_model->get_spk_header($id_spk_budgeting);

        if (empty($header)) {
            show_404();
            return;
        }

        // Get summary for each tipe (excluding Tipe 1 - Subcont Aktifitas)
        $tipes = [
            2 => 'Akomodasi',
            3 => 'Others',
            4 => 'Lab',
            5 => 'Subcont Tenaga Ahli',
            6 => 'Subcont Perusahaan'
        ];

        $summaries = [];
        foreach ($tipes as $tipe_code => $tipe_name) {
            $summary = $this->Cashflow_project_model->get_summary_per_tipe($id_spk_budgeting, $tipe_code);
            $summaries[$tipe_code] = [
                'name' => $tipe_name,
                'budget' => $summary->budget,
                'total_aktual' => $summary->total_aktual,
                'pengajuan_terpakai' => $summary->pengajuan_terpakai,
                'sisa_budget' => $summary->sisa_budget
            ];
        }

        $data = [
            'header' => $header,
            'summaries' => $summaries,
            'id_spk_budgeting' => $id_spk_budgeting
        ];

        $this->template->title('Cashflow Project - Detail');
        $this->template->set($data);
        $this->template->render('view');
    }


    // End Page Function    

    // Get Data Function    

    public function get_data_spk()
    {
        $this->auth->restrict($this->viewPermission);
        $result = $this->Cashflow_project_model->get_data_spk();
        echo json_encode($result);
    }

    public function get_years()
    {
        $this->auth->restrict($this->viewPermission);
        $years = $this->Cashflow_project_model->get_available_years();
        echo json_encode(['success' => true, 'data' => $years]);
    }

    public function get_data_report()
    {
        $this->auth->restrict($this->viewPermission);
        $draw = $this->input->post('draw');
        $start = $this->input->post('start');
        $length = $this->input->post('length');
        $search = $this->input->post('search');
        $year = $this->input->post('year');

        if (empty($year)) {
            $year = (int) date('Y');
        }

        $result = $this->Cashflow_project_model->get_report_data($year, $start, $length, $search);

        echo json_encode([
            'draw' => intval($draw),
            'recordsTotal' => $result['recordsTotal'],
            'recordsFiltered' => $result['recordsFiltered'],
            'data' => $result['data'],
            'grand_total_budget' => $result['grand_total_budget'],
            'grand_total_realisasi' => $result['grand_total_realisasi'],
            'selisih' => $result['selisih']
        ]);
    }

    public function get_data_view_tipe()
    {
        $this->auth->restrict($this->viewPermission);
        $draw = $this->input->post('draw');
        $start = $this->input->post('start');
        $length = $this->input->post('length');
        $search = $this->input->post('search');
        $id_spk_budgeting = $this->input->post('id_spk_budgeting');
        $tipe = $this->input->post('tipe');

        // Decode SPK ID if needed
        if (!empty($id_spk_budgeting)) {
            $id_spk_budgeting = str_replace('|', '/', $id_spk_budgeting);
        }

        $search_value = (!empty($search) && !empty($search['value'])) ? $search['value'] : '';

        $result = $this->Cashflow_project_model->get_transactions_by_tipe($id_spk_budgeting, $tipe, $start, $length, $search_value);

        echo json_encode([
            'draw' => intval($draw),
            'recordsTotal' => $result['recordsTotal'],
            'recordsFiltered' => $result['recordsFiltered'],
            'data' => $result['data']
        ]);
    }

    public function export_excel()
    {
        $this->auth->restrict($this->viewPermission);
        try {
            $year = $this->input->post('year');
            if (empty($year)) {
                $year = (int) date('Y');
            }

            $raw_data = $this->Cashflow_project_model->get_all_report_data($year);

            if (empty($raw_data)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Tidak ada data untuk tahun ' . $year
                ]);
                return;
            }

            // Group data by SPK
            $grouped = [];
            $grand_total_realisasi = 0;
            $grand_total_budget = 0;
            $spk_budgets = [];

            foreach ($raw_data as $item) {
                if ($item['type'] === 'data') {
                    $spk = $item['id_spk_budgeting'];
                    if (!isset($grouped[$spk])) {
                        $spk_penawaran = !empty($item['id_spk_penawaran']) ? $item['id_spk_penawaran'] : $spk;
                        $grouped[$spk] = [
                            'id_spk_budgeting' => $spk,
                            'id_spk_penawaran' => $spk_penawaran,
                            'nm_customer' => $item['nm_customer'],
                            'budget' => (float) $item['budget'],
                            'items' => [],
                            'subtotal' => 0
                        ];
                        $spk_budgets[$spk] = (float) $item['budget'];
                    }
                    $nominal = (float) $item['nominal'];
                    $grouped[$spk]['items'][] = $item;
                    $grouped[$spk]['subtotal'] += $nominal;
                    $grand_total_realisasi += $nominal;
                }
            }

            foreach ($spk_budgets as $b) {
                $grand_total_budget += $b;
            }
            $selisih = $grand_total_budget - $grand_total_realisasi;

            require_once FCPATH . 'vendor/autoload.php';

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $spreadsheet->getProperties()
                ->setCreator("System")
                ->setTitle("Laporan Realisasi Budget Project - {$year}");

            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle("Realisasi Budget - {$year}");
            $sheet->setShowGridlines(true);

            // --- Title Block ---
            $sheet->mergeCells('A1:I1');
            $sheet->setCellValue('A1', "Laporan Realisasi Budget Project – {$year}");
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('1B365D'));
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A1')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension(1)->setRowHeight(25);

            $printedDate = date('d/m/Y');
            $sheet->mergeCells('A2:I2');
            $sheet->setCellValue('A2', "Periode Januari – Desember {$year}  ·  Dicetak {$printedDate}");
            $sheet->getStyle('A2')->getFont()->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('666666'));
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A2')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension(2)->setRowHeight(18);

            // --- Table Header (Row 4) ---
            $headers = [
                'A' => 'NO',
                'B' => 'NO SPK',
                'C' => 'CUSTOMER',
                'D' => 'BUDGET',
                'E' => 'TGL TRANSAKSI',
                'F' => 'NO TRANSAKSI',
                'G' => 'JENIS PENGELUARAN',
                'H' => 'ITEM',
                'I' => 'NOMINAL'
            ];

            foreach ($headers as $col => $title) {
                $sheet->setCellValue($col . '4', $title);
            }

            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'size' => 10,
                    'color' => ['argb' => 'FFFFFFFF']
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1B365D']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    'wrapText' => true
                ]
            ];
            $sheet->getStyle('A4:I4')->applyFromArray($headerStyle);
            $sheet->getRowDimension(4)->setRowHeight(28);

            // --- Data Rows ---
            $currentRow = 5;
            $spkNo = 1;
            $currencyFormat = '"Rp "#,##0';

            $thinBorder = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => 'FFD3D3D3']
                    ]
                ]
            ];

            foreach ($grouped as $spkData) {
                $items = $spkData['items'];
                $itemCount = count($items);
                $startRow = $currentRow;
                $endRow = $startRow + $itemCount - 1;

                // Set Header info on startRow
                $no_spk_display = !empty($spkData['id_spk_penawaran']) ? $spkData['id_spk_penawaran'] : $spkData['id_spk_budgeting'];
                $sheet->setCellValue("A{$startRow}", $spkNo);
                $sheet->setCellValue("B{$startRow}", $no_spk_display);
                $sheet->setCellValue("C{$startRow}", $spkData['nm_customer']);
                $sheet->setCellValue("D{$startRow}", $spkData['budget']);

                // Merge vertical header cells if more than 1 item
                if ($itemCount > 1) {
                    $sheet->mergeCells("A{$startRow}:A{$endRow}");
                    $sheet->mergeCells("B{$startRow}:B{$endRow}");
                    $sheet->mergeCells("C{$startRow}:C{$endRow}");
                    $sheet->mergeCells("D{$startRow}:D{$endRow}");
                }

                // Alignments for header columns
                $sheet->getStyle("A{$startRow}:A{$endRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                $sheet->getStyle("B{$startRow}:B{$endRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                $sheet->getStyle("C{$startRow}:C{$endRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                $sheet->getStyle("D{$startRow}:D{$endRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                $sheet->getStyle("D{$startRow}")->getNumberFormat()->setFormatCode($currencyFormat);

                // Transaction Items
                foreach ($items as $idx => $tx) {
                    $r = $startRow + $idx;

                    $formattedDate = '';
                    if (!empty($tx['tanggal_transaksi'])) {
                        $formattedDate = date('d/m/Y', strtotime($tx['tanggal_transaksi']));
                    }

                    $sheet->setCellValue("E{$r}", $formattedDate);
                    $sheet->setCellValue("F{$r}", $tx['no_transaksi']);
                    $sheet->setCellValue("G{$r}", $tx['jenis_pengeluaran']);
                    $sheet->setCellValue("H{$r}", $tx['item']);
                    $sheet->setCellValue("I{$r}", $tx['nominal']);

                    $sheet->getStyle("E{$r}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                    $sheet->getStyle("F{$r}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                    $sheet->getStyle("G{$r}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                    $sheet->getStyle("H{$r}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                    $sheet->getStyle("I{$r}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                    $sheet->getStyle("I{$r}")->getNumberFormat()->setFormatCode($currencyFormat);

                    $sheet->getRowDimension($r)->setRowHeight(22);
                }

                // Apply borders for data rows
                $sheet->getStyle("A{$startRow}:I{$endRow}")->applyFromArray($thinBorder);

                // --- Subtotal Row ---
                $subtotalRow = $endRow + 1;
                $sheet->mergeCells("A{$subtotalRow}:H{$subtotalRow}");
                $sheet->setCellValue("A{$subtotalRow}", "Subtotal " . $no_spk_display);
                $sheet->getStyle("A{$subtotalRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                $sheet->getStyle("A{$subtotalRow}")->getFont()->setBold(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('1B365D'));

                $sheet->setCellValue("I{$subtotalRow}", $spkData['subtotal']);
                $sheet->getStyle("I{$subtotalRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                $sheet->getStyle("I{$subtotalRow}")->getFont()->setBold(true)->setSize(10);
                $sheet->getStyle("I{$subtotalRow}")->getNumberFormat()->setFormatCode($currencyFormat);

                $sheet->getStyle("A{$subtotalRow}:I{$subtotalRow}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFF4F6F9');

                $sheet->getStyle("A{$subtotalRow}:I{$subtotalRow}")->applyFromArray($thinBorder);
                $sheet->getRowDimension($subtotalRow)->setRowHeight(24);

                $currentRow = $subtotalRow + 1;
                $spkNo++;
            }

            // --- Grand Total Block ---
            $gtStyle = [
                'font' => [
                    'bold' => true,
                    'size' => 10,
                    'color' => ['argb' => 'FFFFFFFF']
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1B365D']
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => 'FFFFFFFF']
                    ]
                ]
            ];

            // 1. Grand Total Budget
            $r1 = $currentRow;
            $sheet->mergeCells("A{$r1}:H{$r1}");
            $sheet->setCellValue("A{$r1}", "Grand Total Budget");
            $sheet->getStyle("A{$r1}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

            $sheet->setCellValue("I{$r1}", $grand_total_budget);
            $sheet->getStyle("I{$r1}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getStyle("I{$r1}")->getNumberFormat()->setFormatCode($currencyFormat);
            $sheet->getStyle("A{$r1}:I{$r1}")->applyFromArray($gtStyle);
            $sheet->getRowDimension($r1)->setRowHeight(24);

            // 2. Grand Total Realisasi
            $r2 = $r1 + 1;
            $sheet->mergeCells("A{$r2}:H{$r2}");
            $sheet->setCellValue("A{$r2}", "Grand Total Realisasi {$year}");
            $sheet->getStyle("A{$r2}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

            $sheet->setCellValue("I{$r2}", $grand_total_realisasi);
            $sheet->getStyle("I{$r2}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getStyle("I{$r2}")->getNumberFormat()->setFormatCode($currencyFormat);
            $sheet->getStyle("A{$r2}:I{$r2}")->applyFromArray($gtStyle);
            $sheet->getRowDimension($r2)->setRowHeight(24);

            // 3. Selisih
            $r3 = $r2 + 1;
            $sheet->mergeCells("A{$r3}:H{$r3}");
            $sheet->setCellValue("A{$r3}", "Selisih (Grand Total Budget – Grand Total Realisasi)");
            $sheet->getStyle("A{$r3}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

            $sheet->setCellValue("I{$r3}", $selisih);
            $sheet->getStyle("I{$r3}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getStyle("I{$r3}")->getNumberFormat()->setFormatCode($currencyFormat);
            $sheet->getStyle("A{$r3}:I{$r3}")->applyFromArray($gtStyle);
            $sheet->getRowDimension($r3)->setRowHeight(24);

            // Set explicit column widths
            $columnWidths = [
                'A' => 6,
                'B' => 24,
                'C' => 32,
                'D' => 20,
                'E' => 15,
                'F' => 18,
                'G' => 22,
                'H' => 35,
                'I' => 20
            ];
            foreach ($columnWidths as $col => $w) {
                $sheet->getColumnDimension($col)->setWidth($w);
            }

            $filename = "Laporan_Realisasi_Budget_Project_{$year}.xlsx";

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        } catch (Exception $e) {
            log_message('error', 'Export Excel Error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Export gagal. ' . (strpos($e->getMessage(), 'memory') !== false ? 'Data terlalu besar.' : 'Terjadi kesalahan.')
            ]);
        }
    }

    // End Data Function

    // Update Data Function



    // End Update Data Function
}
