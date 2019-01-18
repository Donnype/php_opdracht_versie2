<table border='1'>
    <tr>
        <td>date</td><td>persons</td>

        @for($i = 1; $i < 22; $i++)
            <td> {{@$i}} nights</td>
        @endfor
    </tr>

    @for($i = 0; $i <= 7; $i++)
        @foreach($LOS_array as $LOS_person)
        <tr>

            @foreach($LOS_person[$i] as $cell)
                <td>{{@$cell}}</td>
            @endforeach

        </tr>
        @endforeach
    @endfor

</table>