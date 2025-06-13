<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Office Roster</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; background: #fff;">

    <div style="background-color: orange; color: white; text-align: center; padding: 15px; font-size: 20px; font-weight: bold;">
        Office Rosterly
    </div>

    <div style="max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;">
        <p>Hi {{ $user->firstName }}!</p>

        <p>
            Your shift on <strong>{{ \Carbon\Carbon::parse($deletedDate)->format('l, d M Y') }}</strong> has been <span style="color: red;">deleted</span>.<br>
            Below is your updated weekly roster from <strong>{{ \Carbon\Carbon::parse($weekStartDate)->format('d/m') }}</strong> to <strong>{{ \Carbon\Carbon::parse($weekEndDate)->format('d/m') }}</strong>:
        </p>

        <table width="100%" cellpadding="10" cellspacing="0" style="border-collapse: collapse;">
            @foreach($weeklyShifts as $shift)
                @php
                    $date = \Carbon\Carbon::parse($shift['date']);
                    $start = $shift['startTime'] ? \Carbon\Carbon::parse($shift['startTime'])->format('h:i A') : null;
                    $end = $shift['endTime'] ? \Carbon\Carbon::parse($shift['endTime'])->format('h:i A') : null;
                    $break = $shift['breakTime'] ?? 0;
                    $total = $shift['totalHrs'] ?? 0;
                    $isDeleted = $shift['date'] === \Carbon\Carbon::parse($deletedDate)->toDateString();
                @endphp

                <tr style="background-color: {{ $isDeleted ? '#ffe5e5' : '#f7f7f7' }}; border-bottom: 1px solid #ccc;">
                    <td width="45%" style="font-weight: bold; font-size: 14px;">
                        {{ $date->format('D') }}<br>{{ $date->format('d M') }}
                    </td>
                    <td width="55%" align="right" style="font-size: 14px;">
                        @if($start && $end)
                            {{ $start }} - {{ $end }}<br>
                            🍽️ Break: {{ $break }} hr<br>
                            ⏱️ Total: {{ $total }} hr
                        @else
                            <strong style="{{ $isDeleted ? 'color: red;' : '' }}">OFF</strong>
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
    </div>

</body>
</html>
