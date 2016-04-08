<!DOCTYPE html>
<html>
    <head>
        <title>Laravel</title>

        <link href="https://fonts.googleapis.com/css?family=Lato:100" rel="stylesheet" type="text/css">

        <style>
            html, body {
                height: 100%;
            }

            body {
                margin: 0;
                padding: 0;
                width: 100%;
                display: table;
                font-weight: 100;
                font-family: 'Lato';
            }

            .container {
                text-align: center;
                display: table-cell;
                vertical-align: middle;
            }

            .content {
                text-align: center;
                display: inline-block;
            }

            .title {
                font-size: 96px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="content">
                <div class="title">Alerts</div>
                <table>
                  @if (empty($stats))
                    <tr><td>Nothing to report on.</td></tr>
                  @else
                      @foreach ($stats as $stat)
                          <tr><td>{{ $stat }}</td></tr>
                      @endforeach
                   @endif
                  </table>
            </div>
        </div>
    </body>
</html>
