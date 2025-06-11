<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { background-color: orange; color: white; padding: 10px; text-align: center; font-size: 20px; }
        .card { margin: 20px; border: 1px solid #ddd; border-radius: 5px; padding: 20px; }
        .row { display: flex; justify-content: space-between; padding: 10px; background: #f7f7f7; margin-top: 5px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="header">Office Rosterly (UPDATED)</div>
    <div class="card">
        <p>Hi {{ $user->firstName }}!</p>
        <p>Here are your shifts for the week: 
           <strong>{{ \Carbon\Carbon::parse($weekStartDate)->format('d/m') }} to {{ \Carbon\Carbon::parse($weekEndDate)->format('d/m') }}</strong></p>

        @foreach($weeklyShifts as $shift)
            @php
                $date = \Carbon\Carbon::parse($shift['date']);
                $start = !empty($shift['startTime']) ? \Carbon\Carbon::parse($shift['startTime'])->format('h:i A') : null;
                $end = !empty($shift['endTime']) ? \Carbon\Carbon::parse($shift['endTime'])->format('h:i A') : null;
                $break = isset($shift['breakTime']) ? $shift['breakTime'] : 0;
                $total = isset($shift['totalHrs']) ? $shift['totalHrs'] : 0;
            @endphp
            <div class="row">
                <div>
                    {{ $date->format('D') }}<br><strong>{{ $date->format('d M') }}</strong>
                </div>
                <div>
                    @if($start && $end)
                        <div>{{ $start }} - {{ $end }}</div>
                        <div>🍽️ Break: {{ $break }} hr</div>
                        <div>⏱️ Total: {{ $total }} hr</div>
                    @else
                        <div><strong>OFF</strong></div>
                    @endif  
                </div>
            </div>
        @endforeach

    </div>
</body>
</html>
