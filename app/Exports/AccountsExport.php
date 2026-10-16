<?php

namespace App\Exports;

use App\Models\RoForm;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AccountsExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $fromDate;
    protected $toDate;

    public function __construct ($fromDate=null, $toDate=null)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
    }

    public function collection()
    {
        $query = RoForm::with('account')
            ->where('status', 'approved')
            ->orderBy('created_at', 'asc');

        if ($this->fromDate) {
            $query->whereDate('created_at', '>=', $this->fromDate);
        }

        if ($this->toDate) {
            $query->whereDate('created_at', '<=', $this->toDate);
        }

        return $query->get()->map(function ($roForm) {

            $account = $roForm->account;

            return [
                $roForm->ro_number,
                optional($roForm->created_at)->format('d-m-Y'),
                $roForm->vendor_name,

                $account?->vendor_invoice ?? '',
                $account?->vendor_invoice_date
                    ? date('d-m-Y', strtotime($account->vendor_invoice_date))
                    : '',

                $account?->vendor_status ?? '',

                $account?->bill_name ?? '',

                $roForm->service,

                optional($roForm->created_at)->format('d-m-Y'),

                $roForm->completion_date
                    ? \Carbon\Carbon::parse($roForm->completion_date)->format('d-m-Y') : '',

                $roForm->total_amount,

                $account?->external_amount ?? '',

                $account?->anvis_invoice ?? '',

                $account?->anvis_invoice_date
                    ? date('d-m-Y', strtotime($account->anvis_invoice_date))
                    : '',

                $account?->anvis_status ?? '',

                $account?->executive ?? '',

                $account?->comments ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'RO No',
            'Date',
            'Vendor',
            "Vendor's Invoice No",
            "Vendor's Invoice Date",
            'Status',
            'Bill Name',
            'Activity',
            'From',
            'To',
            'INT',
            'EXT',
            'Anvis Invoice No',
            'Anvis Invoice Date',
            'Anvis Status',
            'Executive',
            'Comments',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();

        // Header
        $sheet->getStyle('A1:Q1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E3A5F'],
            ],
        ]);

        // Alternate row color
        for ($row = 2; $row <= $lastRow; $row++) {

            if ($row % 2 == 0) {

                $sheet->getStyle("A{$row}:Q{$row}")
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('EAF1FB');
            }
        }

        // Borders
        $sheet->getStyle("A1:Q{$lastRow}")
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        return [];
    }

}
