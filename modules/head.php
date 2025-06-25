    <head>
        <title><?php echo $pageTitle; ?>Smarter Connect</title>
        <link href="/assets/css/top.css" rel="stylesheet">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, minimum-scale=1.0, user-scalable=yes" />
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta http-equiv="Content-Style-Type" content="text/css" />
        <meta name="viewport" content="width=1260">

        <link rel="apple-touch-icon" sizes="57x57" href="/assets/img/appicon.png" />
        <link rel="apple-touch-icon" sizes="72x72" href="/assets/img/appicon.png" />
        <link rel="apple-touch-icon" sizes="114x114" href="/assets/img/appicon.png" />
        <link rel="apple-touch-icon" sizes="144x144" href="/assets/img/appicon.png" />
        <link rel="shortcut icon" type="image/x-icon" href="/assets/img/appicon.png" />
        <link href="https://use.fontawesome.com/releases/v6.2.0/css/all.css" rel="stylesheet" />
        <!-- Javascript MooTools -->
        <script type="text/javascript"
            src="https://cdnjs.cloudflare.com/ajax/libs/mootools/1.6.0/mootools-core.js"></script>
        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.1.js"></script>
        <script>
            $(document).ready(function() {
                $(".buttons-homepage .loading").click(function() {
                    jQuery('.loading-wrapper').removeClass('inactive');
                    jQuery('.loading-wrapper').addClass('active');
                });
            });
        </script>
    </head>