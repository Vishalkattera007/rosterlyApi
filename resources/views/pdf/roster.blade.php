<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: center; }
        th { background-color: #4CAF50; color: white; }
        .gray { background-color: #eee; }
        .shift-box {
            background-color: #555;
            color: white;
            padding: 5px;
            border-radius: 4px;
        }
        .footer { margin-top: 10px; text-align: right; font-weight: bold; }
    </style>
</head>
<body>
    <h2 style="text-align:center;">Roster Sheet</h2>
    <table>
        <thead>
            <tr>
                <th>Employee</th>
                @foreach($days as $day)
                    <th>{{ $day }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($rosterData as $employee)
                <tr>
                    <td>
                        <strong>{{ $employee['name'] }}</strong><br>
                        {{ number_format($employee['rate'], 2) }} / Hr<br>
                        Weekly: {{ $employee['weekly_hours'] }}
                    </td>
                    @foreach($employee['days'] as $shift)
                        <td>
                            @if($shift)
                                <div class="shift-box">
                                    {{ $shift['start'] }} - {{ $shift['end'] }}<br>
                                    {{ $shift['duration'] }}<br>
                                    ({{ $shift['break'] }})
                                </div>
                            @else
                                —
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Weekly Total: {{ $weeklyTotal }}
    </div>
</body>
</html>
