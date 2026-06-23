<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Entrance Exam Scores - {{ $class }}</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #333333;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #0b5ed7;
            padding-bottom: 10px;
        }

        .school-name {
            font-size: 18px;
            font-weight: bold;
            color: #0b5ed7;
            text-transform: uppercase;
            margin: 0 0 5px 0;
        }

        .school-address {
            font-size: 10px;
            color: #666666;
            margin: 0 0 5px 0;
        }

        .document-title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 10px 0 0 0;
            color: #333333;
            letter-spacing: 0.5px;
        }

        .meta-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 4px 0;
            vertical-align: top;
        }

        .meta-label {
            font-weight: bold;
            color: #555555;
            width: 15%;
        }

        .meta-value {
            color: #333333;
            width: 35%;
        }

        .scores-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .scores-table th {
            background-color: #0b5ed7;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            padding: 8px 6px;
            border: 1px solid #0b5ed7;
            text-align: left;
        }

        .scores-table td {
            padding: 8px 6px;
            border: 1px solid #e2e8f0;
        }

        .scores-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .fw-bold {
            font-weight: bold;
        }

        .text-uppercase {
            text-transform: uppercase;
        }

        .footer {
            margin-top: 50px;
            width: 100%;
        }

        .signature-table {
            width: 100%;
            margin-top: 40px;
            border-collapse: collapse;
        }

        .signature-table td {
            width: 50%;
            text-align: center;
        }

        .signature-line {
            width: 60%;
            margin: 0 auto 5px auto;
            border-bottom: 1px solid #999999;
        }

        .signature-title {
            font-size: 10px;
            color: #555555;
        }

        .date-generated {
            margin-top: 30px;
            font-size: 8px;
            color: #999999;
            text-align: center;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <div class="header">
        <h1 class="school-name">{{ $schoolName }}</h1>
        <p class="school-address">{{ $schoolAddress }}</p>
        <h2 class="document-title">Entrance Examination Class Score Sheet</h2>
    </div>

    <!-- Metadata Section -->
    <table class="meta-table">
        <tr>
            <td class="meta-label">Target Class:</td>
            <td class="meta-value fw-bold">{{ $class }}</td>
            <td class="meta-label">Academic Session:</td>
            <td class="meta-value">{{ $currentSessionName }}</td>
        </tr>
        <tr>
            <td class="meta-label">Exam Batch:</td>
            <td class="meta-value fw-bold">{{ $batch }}</td>
            <td class="meta-label">Date Exported:</td>
            <td class="meta-value">{{ date('F d, Y') }}</td>
        </tr>
        <tr>
            <td class="meta-label">Subjects Count:</td>
            <td class="meta-value">{{ count($subjects) }}</td>
            <td class="meta-label"></td>
            <td class="meta-value"></td>
        </tr>
    </table>

    <!-- Scores Table -->
    <table class="scores-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">S/N</th>
                <th style="width: 15%;">Reg. No</th>
                <th style="width: 35%;">Candidate Name</th>
                @foreach($subjects as $sub)
                    <th class="text-center" style="width: 12%;">{{ $sub->name }}</th>
                @endforeach
                <th style="width: 10%;" class="text-center">Total</th>
                <th style="width: 13%;" class="text-center">Average (%)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($applicants as $index => $applicant)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="fw-bold">{{ $applicant->registration_number }}</td>
                    <td class="text-uppercase">{{ $applicant->full_name }}</td>
                    
                    @php
                        $totalScore = 0;
                        $subjectsCount = 0;
                    @endphp
                    
                    @foreach($subjects as $sub)
                        @php
                            $score = $scoresMap[$applicant->id][$sub->id] ?? null;
                            if ($score !== null) {
                                $totalScore += $score;
                                $subjectsCount++;
                            }
                        @endphp
                        <td class="text-center">{{ $score ?? '-' }}</td>
                    @endforeach
                    
                    <td class="text-center fw-bold">
                        {{ $subjectsCount > 0 ? $totalScore : '-' }}
                    </td>
                    <td class="text-center fw-bold">
                        {{ $subjectsCount > 0 ? round($totalScore / $subjectsCount, 1) . '%' : '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 5 + count($subjects) }}" class="text-center" style="padding: 20px; color: #666666;">
                        No applicants found or registered for this class in this academic session.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Signatures -->
    <table class="signature-table">
        <tr>
            <td>
                <div class="signature-line"></div>
                <div class="signature-title">Admission Officer</div>
            </td>
            <td>
                <div class="signature-line"></div>
                <div class="signature-title">Principal / Director</div>
            </td>
        </tr>
    </table>

    <!-- Generation Info -->
    <div class="date-generated">
        Generated automatically by the Admission Portal on {{ date('Y-m-d H:i:s') }}.
    </div>

</body>
</html>
