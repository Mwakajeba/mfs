<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Loan Outstanding Balance Report</title>
    <style>
        @page { size: A3 landscape; margin: 10mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 9px; color: #000; line-height: 1.3; }
        .header { text-align: center; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 2px solid #000; }
        .logo { max-height: 50px; margin-bottom: 5px; }
        .company-name { font-size: 16px; font-weight: bold; color: #000; margin: 3px 0; }
        .company-details { font-size: 9px; color: #000; margin: 2px 0; }
        .report-title { font-size: 12px; font-weight: bold; color: #000; margin: 8px 0 3px 0; text-transform: uppercase; }
        .report-info { font-size: 9px; color: #000; margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #000; padding: 2px 1px; text-align: left; font-size: 6px; color: #000; }
        th { background-color: #000; color: #fff; font-weight: bold; text-align: center; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { background-color: #f0f0f0; font-weight: bold; }
        .footer { margin-top: 15px; padding-top: 8px; border-top: 1px solid #000; text-align: center; font-size: 8px; color: #000; }
        .footer p { margin: 2px 0; }
        .digital-signature { margin-top: 5px; font-size: 7px; color: #000; font-style: italic; }
    </style>
</head>
<body>
    @php
        $logoBase64 = null;
        $logoPath = null;
        if (isset($company) && $company && !empty($company->logo)) {
            $storagePath = public_path('storage/' . $company->logo);
            if (file_exists($storagePath)) { $logoPath = $storagePath; }
        }
        if (!$logoPath && file_exists(public_path('assets/images/logo-img.png'))) {
            $logoPath = public_path('assets/images/logo-img.png');
        }
        if ($logoPath && file_exists($logoPath)) {
            $logoType = pathinfo($logoPath, PATHINFO_EXTENSION);
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/' . $logoType . ';base64,' . base64_encode($logoData);
        }
    @endphp

    <!-- Header -->
    <div class="header">
        @if($logoBase64)<img src="{{ $logoBase64 }}" alt="Logo" class="logo">@endif
        <div class="company-name">{{ $company->name ?? config('app.name', 'SmartFinance') }}</div>
        @if(isset($company) && $company)
            @if($company->address)<div class="company-details">{{ $company->address }}</div>@endif
            <div class="company-details">
                @if($company->phone)Phone: {{ $company->phone }}@endif
                @if($company->phone && $company->email) | @endif
                @if($company->email)Email: {{ $company->email }}@endif
            </div>
        @endif
        <div class="report-title">Loan Outstanding Balance Report</div>
        <div class="report-info"><strong>Branch:</strong> {{ $branch->name ?? 'All Branches' }} | <strong>Loan Officer:</strong> {{ $loanOfficer->name ?? 'All Officers' }}</div>
        <div class="report-info"><strong>As of Date:</strong> {{ \Carbon\Carbon::parse($asOfDate)->format('d/m/Y') }} | <strong>Report Date:</strong> {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}</div>
    </div>

    <!-- Data Table -->
    <table>
        <thead>
            <tr>
                <th style="width: 2%;">S/N</th>
                <th style="width: 7%;">Customer</th>
                <th style="width: 4%;">Cust No</th>
                <th style="width: 4%;">Phone</th>
                <th style="width: 4%;">Loan No</th>
                <th style="width: 4%;">Disbursed</th>
                <th style="width: 4%;">Exp Int</th>
                <th style="width: 4%;">Amt Pay</th>
                <th style="width: 4%;">Exp Fees</th>
                <th style="width: 4%;">Pen Sched</th>
                <th style="width: 4%;">Disb Dt</th>
                <th style="width: 4%;">Expiry</th>
                <th style="width: 4%;">Branch</th>
                <th style="width: 4%;">Officer</th>
                <th style="width: 4%;">Princ Paid</th>
                <th style="width: 4%;">Int Paid</th>
                <th style="width: 4%;">Fee Paid</th>
                <th style="width: 4%;">Pen Paid</th>
                <th style="width: 4%;">Out Princ</th>
                <th style="width: 4%;">Out Int</th>
                <th style="width: 4%;">Accr Int</th>
                <th style="width: 4%;">ND Int</th>
                <th style="width: 4%;">Out Fee</th>
                <th style="width: 4%;">Out Pen</th>
                <th style="width: 4%;">Out Bal</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalDisbursed = 0;
                $totalInterest = 0;
                $totalAmountToPay = 0;
                $totalExpectedFees = 0;
                $totalScheduledPenalty = 0;
                $totalPrincipalPaid = 0;
                $totalInterestPaid = 0;
                $totalFeesPaid = 0;
                $totalPenaltyPaid = 0;
                $totalOutPrincipal = 0;
                $totalOutInterest = 0;
                $totalAccrued = 0;
                $totalNotDue = 0;
                $totalOutFees = 0;
                $totalOutPenalty = 0;
                $totalOutBalance = 0;
                $count = 0;
            @endphp
            @forelse($outstandingData as $index => $row)
                @php
                    $count++;
                    $totalDisbursed += $row['amount'] ?? 0;
                    $totalInterest += $row['interest'] ?? 0;
                    $totalAmountToPay += $row['amount_to_pay'] ?? 0;
                    $totalExpectedFees += $row['expected_fees'] ?? 0;
                    $totalScheduledPenalty += $row['scheduled_penalty'] ?? 0;
                    $totalPrincipalPaid += $row['principal_paid'] ?? 0;
                    $totalInterestPaid += $row['interest_paid'] ?? 0;
                    $totalFeesPaid += $row['fees_paid'] ?? 0;
                    $totalPenaltyPaid += $row['penalty_paid'] ?? 0;
                    $outPrincipal = ($row['amount'] ?? 0) - ($row['principal_paid'] ?? 0);
                    $totalOutPrincipal += $outPrincipal;
                    $totalOutInterest += $row['outstanding_interest'] ?? 0;
                    $totalAccrued += $row['accrued_interest'] ?? 0;
                    $totalNotDue += $row['not_due_interest'] ?? 0;
                    $totalOutFees += $row['outstanding_fees'] ?? 0;
                    $totalOutPenalty += $row['outstanding_penalty'] ?? 0;
                    $totalOutBalance += $row['outstanding_balance'] ?? 0;
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $row['customer'] }}</td>
                    <td class="text-center">{{ $row['customer_no'] }}</td>
                    <td class="text-center">{{ $row['phone'] }}</td>
                    <td class="text-center">{{ $row['loan_no'] }}</td>
                    <td class="text-right">{{ number_format($row['amount'], 0) }}</td>
                    <td class="text-right">{{ number_format($row['interest'], 0) }}</td>
                    <td class="text-right">{{ number_format($row['amount_to_pay'] ?? 0, 0) }}</td>
                    <td class="text-right">{{ number_format($row['expected_fees'] ?? 0, 0) }}</td>
                    <td class="text-right">{{ number_format($row['scheduled_penalty'] ?? 0, 0) }}</td>
                    <td class="text-center">{{ $row['disbursed_no'] }}</td>
                    <td class="text-center">{{ $row['expiry'] }}</td>
                    <td>{{ $row['branch'] }}</td>
                    <td>{{ $row['loan_officer'] }}</td>
                    <td class="text-right">{{ number_format($row['principal_paid'], 0) }}</td>
                    <td class="text-right">{{ number_format($row['interest_paid'], 0) }}</td>
                    <td class="text-right">{{ number_format($row['fees_paid'] ?? 0, 0) }}</td>
                    <td class="text-right">{{ number_format($row['penalty_paid'] ?? 0, 0) }}</td>
                    <td class="text-right">{{ number_format($outPrincipal, 0) }}</td>
                    <td class="text-right">{{ number_format($row['outstanding_interest'], 0) }}</td>
                    <td class="text-right">{{ number_format($row['accrued_interest'], 0) }}</td>
                    <td class="text-right">{{ number_format($row['not_due_interest'], 0) }}</td>
                    <td class="text-right">{{ number_format($row['outstanding_fees'] ?? 0, 0) }}</td>
                    <td class="text-right">{{ number_format($row['outstanding_penalty'] ?? 0, 0) }}</td>
                    <td class="text-right">{{ number_format($row['outstanding_balance'], 0) }}</td>
                </tr>
            @empty
                <tr><td colspan="25" class="text-center">No records found</td></tr>
            @endforelse
            <!-- Total Row -->
            <tr class="total-row">
                <td class="text-center" colspan="2"><strong>TOTAL</strong></td>
                <td colspan="3" class="text-right"><strong>{{ number_format($count) }} Rec</strong></td>
                <td class="text-right"><strong>{{ number_format($totalDisbursed, 0) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totalInterest, 0) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totalAmountToPay, 0) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totalExpectedFees, 0) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totalScheduledPenalty, 0) }}</strong></td>
                <td colspan="4"></td>
                <td class="text-right"><strong>{{ number_format($totalPrincipalPaid, 0) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totalInterestPaid, 0) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totalFeesPaid, 0) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totalPenaltyPaid, 0) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totalOutPrincipal, 0) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totalOutInterest, 0) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totalAccrued, 0) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totalNotDue, 0) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totalOutFees, 0) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totalOutPenalty, 0) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totalOutBalance, 0) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <!-- Footer -->
    <div class="footer">
        <p><strong>&copy; {{ date('Y') }} {{ $company->name ?? config('app.name', 'SmartFinance') }}. All Rights Reserved.</strong></p>
        <p class="digital-signature">This is a digitally generated document from {{ $company->name ?? config('app.name', 'SmartFinance') }} System. No signature required.</p>
        <p class="digital-signature">Generated on: {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }} | Document ID: {{ strtoupper(uniqid('DOC-')) }}</p>
    </div>
</body>
</html>
