<?php

namespace App\Http\Controllers;

use App\Imports\AssetsImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportController extends Controller
{
    public function __construct()
    {
        // Only ADMIN can import
    }

    /**
     * Show import form.
     */
    public function create()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return inertia('Assets/Import');
    }

    /**
     * Process the uploaded Excel file.
     */
    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ], [
            'file.required' => 'File Excel wajib diunggah.',
            'file.mimes'    => 'Format file harus .xlsx, .xls, atau .csv.',
            'file.max'      => 'Ukuran file maksimal 5MB.',
        ]);

        try {
            $import = new AssetsImport();
            Excel::import($import, $request->file('file'));

            $message = "Berhasil mengimpor {$import->imported} aset.";

            if (!empty($import->errors)) {
                $errorList = implode(' | ', array_slice($import->errors, 0, 5));
                return redirect()->route('assets.index')
                    ->with('warning', "{$message} Beberapa baris dilewati: {$errorList}");
            }

            return redirect()->route('assets.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses file: ' . $e->getMessage());
        }
    }

    /**
     * Download Excel template for import.
     */
    public function template(): BinaryFileResponse
    {
        $templatePath = storage_path('app/templates/import_template.xlsx');

        // Generate template on-the-fly if not exists
        if (!file_exists($templatePath)) {
            $this->generateTemplate($templatePath);
        }

        return response()->download($templatePath, 'template_import_aset.xlsx');
    }

    private function generateTemplate(string $path): void
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Aset');

        // Headers
        $headers = [
            'A1' => 'nama_barang',
            'B1' => 'merk',
            'C1' => 'tahun',
            'D1' => 'kode_barang',
            'E1' => 'tipe',
            'F1' => 'kondisi',
            'G1' => 'stok',
            'H1' => 'jurusan',
            'I1' => 'kategori',
            'J1' => 'lokasi',
            'K1' => 'keterangan',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Style header row
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4F46E5']],
        ];
        $sheet->getStyle('A1:K1')->applyFromArray($headerStyle);

        // Example row
        $sheet->setCellValue('A2', 'Proyektor Epson EB-X41');
        $sheet->setCellValue('B2', 'Epson');
        $sheet->setCellValue('C2', '2022');
        $sheet->setCellValue('D2', 'PRY-001');
        $sheet->setCellValue('E2', 'FIXED');
        $sheet->setCellValue('F2', 'BAIK');
        $sheet->setCellValue('G2', '');
        $sheet->setCellValue('H2', 'Teknik Informatika');
        $sheet->setCellValue('I2', 'Elektronik');
        $sheet->setCellValue('J2', 'Lab Komputer 1');
        $sheet->setCellValue('K2', 'Proyektor kelas A');

        // Auto-size columns
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Add notes sheet
        $notesSheet = $spreadsheet->createSheet();
        $notesSheet->setTitle('Petunjuk');
        $notesSheet->setCellValue('A1', 'PETUNJUK PENGISIAN');
        $notesSheet->setCellValue('A3', 'Kolom tipe:');
        $notesSheet->setCellValue('B3', 'FIXED (Aset Tetap) atau CONSUMABLE (Barang Habis Pakai)');
        $notesSheet->setCellValue('A4', 'Kolom kondisi:');
        $notesSheet->setCellValue('B4', 'BAIK, RUSAK RINGAN, atau RUSAK BERAT');
        $notesSheet->setCellValue('A5', 'Kolom stok:');
        $notesSheet->setCellValue('B5', 'Isi hanya jika tipe = CONSUMABLE, kosongkan jika FIXED');
        $notesSheet->setCellValue('A6', 'Kolom kode_barang:');
        $notesSheet->setCellValue('B6', 'Boleh dikosongkan, sistem akan generate otomatis jika kosong');

        // Save
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($path);
    }
}
