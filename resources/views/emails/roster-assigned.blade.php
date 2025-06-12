<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Office Roster</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #fff;
        }

        .header {
            background-color: orange;
            color: white;
            text-align: center;
            padding: 15px;
            font-size: 20px;
            font-weight: bold;
        }

        .card {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #ddd;
        }

        .row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 15px;
            border-radius: 6px;
            background-color: #f7f7f7;
            margin-bottom: 10px;
        }

        .left {
            width: 40%;
            font-weight: bold;
            font-size: 15px;
            line-height: 1.5;
        }

        .right {
            width: 55%;
            text-align: right;
            font-size: 14px;
            line-height: 1.5;
        }

        /* ✅ Remove flex-direction column — keep it side-by-side on mobile */
        @media (max-width: 600px) {
            .left, .right {
                width: 48%;
                font-size: 14px;
            }
            .right {
                text-align: right;
            }
        }
    </style>
</head>
<body>
    <div class="header">Office Rosterly</div>
    <div class="card">
        <p>Hi {{ $user->firstName }}!</p>
        <p>Here are your shifts for the week:
            <strong>{{ \Carbon\Carbon::parse($weekStartDate)->format('d/m') }} to {{ \Carbon\Carbon::parse($weekEndDate)->format('d/m') }}</strong>
        </p>

        @foreach($weeklyShifts as $shift)
            @php
                $date = \Carbon\Carbon::parse($shift['date']);
                $start = !empty($shift['startTime']) ? \Carbon\Carbon::parse($shift['startTime'])->format('h:i A') : null;
                $end = !empty($shift['endTime']) ? \Carbon\Carbon::parse($shift['endTime'])->format('h:i A') : null;
                $break = isset($shift['breakTime']) ? $shift['breakTime'] : 0;
                $total = isset($shift['totalHrs']) ? $shift['totalHrs'] : 0;
            @endphp
            <div class="row">
                <div class="left">
                    {{ $date->format('D') }}<br>{{ $date->format('d M') }}
                </div>
                <div class="right">
                    @if($start && $end)
                        {{ $start }} - {{ $end }}<br>
                        🍽️ Break: {{ $break }} hr<br>
                        ⏱️ Total: {{ $total }} hr
                    @else
                        <strong>OFF</strong>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>
