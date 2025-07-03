<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background-color: #f0f0f0; }
    </style>
</head>
<body>
    <h2>Timesheet Summary</h2>
    <p><strong>Employee:</strong> {{ $employee }}</p>
    <p><strong>Location:</strong>{{$location_id}}</p>
    <p><strong>Week:</strong> {{ $week }}</p>

    <table>
        <thead>
            <tr>
                <th>Day</th>
                <th>Scheduled Shift</th>
                <th>Scheduled Break Time</th>
                <th>Actual Working Time</th>
                <th>Actual Break Time</th>
                <th>Overtime</th>
                <th>Less Time</th>
                <th>Pay</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['day'] }}</td>
                    <td>{{ $row['scheduled_shift'] }}</td>
                    <td>{{ $row['scheduled_break'] }}</td>
                    <td>{{ $row['actual_working'] }}</td>
                    <td>{{ $row['actual_break'] }}</td>
                    <td>{{ $row['overtime'] }}</td>
                    <td>{{ $row['less_time'] }}</td>
                    <td>${{ $row['pay'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <br><br>
    <p><strong>Total Overtime Hours:</strong> {{ $totalOvertime }}</p>
    <p><strong>Total Less Time Hours:</strong> {{ $totalLessTime }}</p>
    <p><strong>Total Pay:</strong> <strong style="color: green">${{ $totalPay }}</strong></p>
</body>
</html>
