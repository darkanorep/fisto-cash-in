<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat\DateFormatter;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Spatie\Activitylog\Models\Activity;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ActivityExport implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting
{
    /**
     * @var mixed|null
     */
    private mixed $fromDate;
    /**
     * @var mixed|null
     */
    private mixed $toDate;
    /**
     * @var mixed|null
     */
    private mixed $status;
    /**
     * @var mixed|null
     */
    private mixed $mode_of_payment;

    /**
    * @return array[]
     */

    public function __construct($fromDate = null, $toDate = null, $status = null, $mode_of_payment = null)
    {
        $this->fromDate = $fromDate ? Carbon::createFromFormat('Y-m-d', $fromDate)->startOfDay() : null;
        $this->toDate = $toDate ? Carbon::createFromFormat('Y-m-d', $toDate)->endOfDay() : null;
        $this->status = $status ?? null;
        $this->mode_of_payment = $mode_of_payment ?? null;
    }

    public function columnFormats(): array
    {
        return [
            'D' => 'yyyy-mm-dd hh:mm AM/PM',
            'E' => 'yyyy-mm-dd hh:mm AM/PM'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF4472C4'],
                ],
            ],
        ];
    }

    public function headings(): array {
        return [
            'Type',
            'Category',
            'Reference No',
            'Transaction Date',
            'Payment Date',
            'Customer',
            'Mode of Payment',
            'Bank',
            'Check No',
            'Check Date',
            'Amount',
            'Charging',
            'Remarks',
            'Deposit Date',
            'Bank Deposit',
            'Deposit Remarks',
            'Tag No'
        ];
    }

    public function collection()
    {
        $query = Activity::leftJoin('transactions', 'transactions.id', '=', 'activity_log.subject_id')
            ->select(
                'transactions.type',
                'transactions.category',
                'transactions.reference_no',
                'transactions.transaction_date',
                'transactions.payment_date',
                'transactions.customer_name',
                'transactions.mode_of_payment',
                'transactions.bank_name',
                'transactions.check_no',
                'transactions.check_date',
                'transactions.amount',
                'transactions.charge_name',
                'transactions.remarks',
                'transactions.deposit_date',
                'transactions.bank_deposit',
                'transactions.deposit_remarks',
                'transactions.tag_number',
            )
            ->whereBetween('activity_log.created_at', [$this->fromDate, $this->toDate])
            ->where('transactions.mode_of_payment', $this->mode_of_payment)
            ->where('activity_log.event', 'tag:'.$this->status)
            ->get();

        return $query->map(function ($item) {
            if ($item->transaction_date || $item->payment_date ) {
                $item->transaction_date = Carbon::parse($item->transaction_date)->format('Y-m-d h:i A');
                $item->payment_date = Carbon::parse($item->payment_date)->format('Y-m-d h:i A');
            }
            return $item;
        });
    }

}
