<table border='1'>
    <tr>
        <td>date</td><td>persons</td>

        @for($i = 1; $i < 22; $i++)
            <td> {{@$i}} nights</td>
        @endfor
    </tr>

    @foreach($LOS_array as $LOS_row)
        <tr>
        @foreach($LOS_row as $cell)
                <td>{{@$cell}}</td>
        @endforeach
        </tr>
    @endforeach

</table>