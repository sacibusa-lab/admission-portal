<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admission Applicants Report</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }

        .header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .school-title {
            font-size: 18px;
            font-weight: bold;
            color: #0b5ed7;
            text-transform: uppercase;
            margin: 0;
        }

        .report-subtitle {
            font-size: 12px;
            font-weight: bold;
            color: #1e293b;
            margin: 5px 0 0 0;
            text-transform: uppercase;
        }

        .filter-details {
            font-size: 10px;
            color: #64748b;
            margin: 3px 0 0 0;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .table th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }

        .table td {
            padding: 6px 10px;
            border: 1px solid #e2e8f0;
        }

        .table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .badge {
            display: inline-block;
            padding: 2px 5px;
            font-size: 8px;
            font-weight: bold;
            border-radius: 3px;
        }
    </style>
</head>
<body>

    <!-- Report Header -->
    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td>
                    <div class="school-title">{{ $schoolName }}</div>
                    <div class="report-subtitle">Applicants Enrollment Report</div>
                    <div class="filter-details">
                        Filters Applied: Class: {{ $filterDetails['class'] }} | Status: {{ $filterDetails['status'] }} | Date: {{ $filterDetails['date'] }}
                    </div>
                </td>
                <td style="text-align: right; vertical-align: bottom; font-size: 9px; color: #64748b;">
                    Generated on: {{ now()->format('Y-m-d H:i:s') }}
                </td>
            </tr>
        </table>
    </div>

    <!-- Applicants Table -->
    <table class="table">
        <thead>
            <tr>
                <th style="width: 30px;">S/N</th>
                <th style="width: 90px;">Reg. Number</th>
                <th>Full Name</th>
                <th style="width: 50px;">Gender</th>
                <th style="width: 50px;">Class</th>
                <th style="width: 80px;">Parent Phone</th>
                <th style="width: 90px;">Status</th>
                <th style="width: 110px;">Date Registered</th>
            </tr>
        </thead>
        <tbody>
            @forelse($applicants as $index => $applicant)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td style="font-weight: bold; color: #0f172a;">{{ $applicant->registration_number }}</td>
                    <td><strong>{{ $applicant->full_name }}</strong></td>
                    <td>{{ $applicant->gender }}</td>
                    <td>{{ $applicant->class_applying_for }}</td>
                    <td>{{ $applicant->parent_phone_number }}</td>
                    <td>{{ $applicant->admission_status }}</td>
                    <td>{{ $applicant->created_at->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 20px;" class="text-muted">
                        No applicants found matching the search criteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
