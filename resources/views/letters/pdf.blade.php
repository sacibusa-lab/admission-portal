<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admission Letter - {{ $applicant->registration_number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 14px;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #cbd5e1;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }

        .school-name {
            font-size: 24px;
            font-weight: bold;
            color: #0b5ed7;
            text-transform: uppercase;
            margin: 0;
        }

        .school-details {
            font-size: 12px;
            color: #64748b;
            margin: 5px 0 0 0;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .meta-table td {
            vertical-align: top;
            padding: 5px 0;
        }

        .title {
            text-align: center;
            margin: 25px 0;
        }

        .title h3 {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1.5px solid #1e293b;
            display: inline-block;
            padding-bottom: 3px;
            margin: 0;
        }

        .content {
            margin-bottom: 50px;
            white-space: pre-line;
        }

        .signature-table {
            width: 100%;
            margin-top: 60px;
        }

        .signature-line {
            border-top: 1px dashed #475569;
            width: 200px;
            text-align: center;
            padding-top: 5px;
            font-size: 12px;
            color: #475569;
        }
    </style>
</head>
<body>

    <!-- Letterhead -->
    <div class="header">
        <div class="school-name">{{ $schoolName }}</div>
        <div class="school-details">
            {{ $schoolAddress }}<br>
            Email: {{ $schoolEmail }} | Phone: {{ $schoolPhone }}
        </div>
    </div>

    <!-- Metadata Details -->
    <table class="meta-table">
        <tr>
            <td style="width: 60%;">
                Date: <strong>{{ now()->format('d M, Y') }}</strong><br><br>
                To:<br>
                <strong style="font-size: 16px; color: #0f172a;">{{ $applicant->full_name }}</strong><br>
                {{ $applicant->address }}
            </td>
            <td style="width: 40%; text-align: right;">
                Registration No:<br>
                <strong style="font-size: 18px; color: #0b5ed7;">{{ $applicant->registration_number }}</strong>
            </td>
        </tr>
    </table>

    <!-- Letter Title -->
    <div class="title">
        <h3>Letter of Provisional Admission</h3>
    </div>

    <!-- Letter Body content -->
    <div class="content">
        {!! nl2br(e($letterContent)) !!}
    </div>

    <!-- Signatures -->
    <table class="signature-table">
        <tr>
            <td style="width: 50%;">
                <!-- Left spacing -->
            </td>
            <td style="width: 50%; text-align: right;">
                <table style="float: right;">
                    <tr>
                        <td class="signature-line">
                            Principal / Registrar Office
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

</body>
</html>
