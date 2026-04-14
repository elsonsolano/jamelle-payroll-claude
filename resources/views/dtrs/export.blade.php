<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DTR Export</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1a1a1a;
            padding: 28px 32px;
        }

        .doc-header {
            margin-bottom: 18px;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 10px;
        }

        .doc-title {
            font-size: 14px;
            font-weight: bold;
            color: #111827;
        }

        .doc-date {
            font-size: 10px;
            color: #6b7280;
            margin-top: 3px;
        }

        .branch-heading {
            font-size: 12px;
            font-weight: bold;
            color: #1d4ed8;
            text-decoration: underline;
            margin-top: 20px;
            margin-bottom: 10px;
        }

        .employee-heading {
            font-size: 10px;
            font-weight: bold;
            color: #374151;
            margin-bottom: 5px;
            margin-top: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        thead th {
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 4px 6px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            color: #374151;
        }

        tbody td {
            border: 1px solid #e5e7eb;
            padding: 4px 6px;
            font-size: 9px;
            color: #374151;
            vertical-align: top;
        }

        .col-date    { width: 12%; }
        .col-time    { width: 10%; }
        .col-hours    { width: 6%; }
        .col-billable { width: 6%; }
        .col-ot       { width: 6%; }
        .col-late    { width: 6%; }
        .col-ut      { width: 6%; }
        .col-restday { width: 7%; }

        .badge-rest {
            display: inline-block;
            background-color: #fef3c7;
            color: #92400e;
            font-size: 7.5px;
            font-weight: bold;
            padding: 1px 4px;
            border-radius: 3px;
        }

        .text-gray { color: #9ca3af; }
    </style>
</head>
<body>

    <div class="doc-header">
        <div class="doc-title">Daily Time Record (DTR) Export</div>
        <div class="doc-date">
            @if($dateFrom && $dateTo)
                Date: {{ $dateFrom }} &ndash; {{ $dateTo }}
            @elseif($dateFrom)
                Date: From {{ $dateFrom }}
            @elseif($dateTo)
                Date: Up to {{ $dateTo }}
            @else
                Date: All records
            @endif
        </div>
    </div>

    @forelse($grouped as $branchName => $employeeGroups)

        <div class="branch-heading">Branch: {{ $branchName }}</div>

        @foreach($employeeGroups as $employeeId => $dtrs)
            @php $employee = $dtrs->first()->employee; @endphp

            <div class="employee-heading">Employee: {{ $employee->full_name }}</div>

            <table>
                <thead>
                    <tr>
                        <th class="col-date">Date</th>
                        <th class="col-time">Time In</th>
                        <th class="col-time">Start Break</th>
                        <th class="col-time">End Break</th>
                        <th class="col-time">Time Out</th>
                        <th class="col-hours">Hours</th>
                        <th class="col-billable">Billable</th>
                        <th class="col-ot">OT</th>
                        <th class="col-late">Late</th>
                        <th class="col-ut">UT</th>
                        <th class="col-restday">Rest Day</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dtrs as $dtr)
                    <tr>
                        <td>
                            {{ $dtr->date->format('M d, Y') }}<br>
                            <span class="text-gray">{{ $dtr->date->format('l') }}</span>
                        </td>
                        <td>{{ $dtr->time_in    ? \Carbon\Carbon::parse($dtr->time_in)->format('h:i A')  : '—' }}</td>
                        <td>{{ $dtr->am_out     ? \Carbon\Carbon::parse($dtr->am_out)->format('h:i A')   : '—' }}</td>
                        <td>{{ $dtr->pm_in      ? \Carbon\Carbon::parse($dtr->pm_in)->format('h:i A')    : '—' }}</td>
                        <td>
                            {{ $dtr->time_out ? \Carbon\Carbon::parse($dtr->time_out)->format('h:i A') : '—' }}
                            @if($dtr->time_in && $dtr->time_out && \Carbon\Carbon::createFromTimeString($dtr->time_out)->lte(\Carbon\Carbon::createFromTimeString($dtr->time_in)))
                                <br><span style="color:#f97316;font-size:8px;font-weight:bold;">+1 day</span>
                            @endif
                        </td>
                        <td>{{ $dtr->time_in ? number_format($dtr->total_hours, 2) : '—' }}</td>
                        <td>{{ $dtr->time_in ? number_format(min((float) $dtr->total_hours, 8.0), 2) : '—' }}</td>
                        <td>
                            @if($dtr->overtime_hours > 0 && $dtr->ot_status !== 'rejected')
                                {{ number_format($dtr->overtime_hours, 2) }}
                            @else
                                <span class="text-gray">—</span>
                            @endif
                        </td>
                        <td>
                            @if($dtr->late_mins > 0)
                                {{ $dtr->late_mins }} min
                            @else
                                <span class="text-gray">—</span>
                            @endif
                        </td>
                        <td>
                            @if($dtr->undertime_mins > 0)
                                {{ $dtr->undertime_mins }} min
                            @else
                                <span class="text-gray">—</span>
                            @endif
                        </td>
                        <td style="text-align:center">
                            @if($dtr->is_rest_day)
                                <span class="badge-rest">Yes</span>
                            @else
                                <span class="text-gray">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

        @endforeach

    @empty
        <p style="color: #6b7280; margin-top: 20px;">No DTR records found for the selected filters.</p>
    @endforelse

</body>
</html>
