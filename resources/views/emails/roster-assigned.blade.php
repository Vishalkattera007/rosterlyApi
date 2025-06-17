<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Office Roster</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; margin: 0; padding: 0; background: #fff;">

    <div style="background-color: orange; color: white; text-align: center; padding: 15px; font-size: 20px; font-weight: bold;">
        Office Rosterly
    </div>

    <div style="max-width: 600px; margin: 20px auto; padding: 20px; border-radius: 10px; border: 1px solid #ddd;">
        <p>Hi {{ $user->firstName }}!</p>
        <p>
            Here are your shifts for the week:
            <strong>{{ \Carbon\Carbon::parse($weekStartDate)->format('d/m') }} to {{ \Carbon\Carbon::parse($weekEndDate)->format('d/m') }}</strong>
        </p>

        <table width="100%" cellpadding="10" cellspacing="0" style="border-collapse: collapse;">
            @foreach($weeklyShifts as $shift)
                @php
                    $date = \Carbon\Carbon::parse($shift['date']);
                    $start = !empty($shift['startTime']) ? \Carbon\Carbon::parse($shift['startTime'])->format('h:i A') : null;
                    $end = !empty($shift['endTime']) ? \Carbon\Carbon::parse($shift['endTime'])->format('h:i A') : null;
                    $break = isset($shift['breakTime']) ? $shift['breakTime'] : 0;
                    $total = isset($shift['totalHrs']) ? $shift['totalHrs'] : 0;
                @endphp
                <tr style="background-color: #f7f7f7; border-bottom: 1px solid #ccc;">
                    <td width="45%" style="font-weight: bold; font-size: 14px;">
                        {{ $date->format('D') }}<br>{{ $date->format('d M') }}
                    </td>
                    <td width="55%" align="right" style="font-size: 14px;">
                        @if($start && $end)
                            {{ $start }} - {{ $end }}<br>
                            🍽️ Break: {{ $break }} min<br>
                            ⏱️ Total: {{ $total }} hr
                        @else
                            <strong>OFF</strong>
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
    </div>

</body>
</html>
