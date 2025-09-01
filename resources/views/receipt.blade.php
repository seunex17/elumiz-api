<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <style>
        body {
            font-family: 'sans-serif', 'Arial';
            font-size: 12px;
            /* Start here and adjust */
            line-height: 1;
            /* Match your printer's effective width */
            /* ... other styles ... */
        }

        <style>
             /* Spacing and Layout */
         .row {
             display: flex;
             flex-wrap: wrap;
             margin-right: -15px;
             margin-left: -15px;
         }
        .my-3 {
            margin-top: 1rem;
            margin-bottom: 1rem;
        }
        .px-4 {
            padding-right: 1.5rem;
            padding-left: 1.5rem;
        }
        .pt-1 {
            padding-top: 0.25rem;
        }
        .pt-2 {
            padding-top: 0.5rem;
        }
        .col-12 {
            flex: 0 0 100%;
            max-width: 100%;
        }

        /* Alignment */
        .align-items-center {
            align-items: center;
        }
        .text-center {
            text-align: center !important;
        }

        /* Typography */
        .h3 {
            font-size: 1.75rem;
            font-weight: 500;
            line-height: 1.2;
        }
        .h5 {
            font-size: 1.25rem;
            font-weight: 500;
            line-height: 1.2;
        }
        .fs-6 {
            font-size: 1rem !important;
        }
        .fw-light {
            font-weight: 300 !important;
        }
        .fw-medium {
            font-weight: 500 !important;
        }
        .fw-semibold {
            font-weight: 600 !important;
        }
        .fw-bold {
            font-weight: 700 !important;
        }

        /* Table */
        .table-responsive {
            display: block;
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .table {
            width: 100%;
            margin-bottom: 1rem;
            color: #212529;
            border-collapse: collapse;
        }
        .table-bordered {
            border: 1px solid #dee2e6;
        }
        .table-bordered th,
        .table-bordered td {
            border: 1px solid #dee2e6;
        }
        .table th,
        .table td {
            padding: 0.75rem;
            vertical-align: top;
            border-top: 1px solid #dee2e6;
        }
        .table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #dee2e6;
        }

        /* Other */
        .rounded-3 {
            border-radius: 0.3rem !important;
        }
        .overflow-hidden {
            overflow: hidden !important;
        }

        .table-responsive {
            margin-top: 20px;
        }
        .table th, .table td {
            vertical-align: middle; /* Center content vertically */
            padding: 8px; /* Add some padding for better spacing */
        }
        .table thead th {
            background-color: #007bff; /* Bootstrap primary blue for header */
            color: white;
            border-bottom: 2px solid #dee2e6;
            text-align: center; /* Center header text */
        }
        .table tbody tr:nth-child(even) {
            background-color: #f2f2f2; /* Zebra striping */
        }
        .table tbody tr:hover {
            background-color: #e9ecef; /* Hover effect */
        }

        sss
    </style>
</head>
<body>

    <body>
        <div class="">
            <div style="" class="px-4">
                <div class="row my-3 align-items-center">
                    <div class="col-12 text-center">
                        <h3 style="font-size: 30px;">EL-MUIZ MEDICINE STORE</h3>
                        <h3 style="font-size: 16px">No 2, Beside Ohuda Market,</h3>
                        <h2 style="font-size: 16px">Ozuja-Okengwe, Okene, Kogi State.</h2>
                        <h5>08065308094</h5>
                    </div>
                </div>
                <div class="text-center">
                    <h3 class="fw-light" style="font-size: 18px;"> {{ now()->format('l F d Y h:i a') }} </h3>
                </div>

                <div class="table-responsive rounded-3">
                    <table class="table table-bordered rounded-3 overflow-hidden">
                        <thead>
                        <tr>
                            <th scope="col" class="fs-6">ITEM NAME</th>
                            <th scope="col" class="fs-6">QTY</th>
                            <th scope="col" class="fs-6">PRICE</th>
                            <th scope="col" class="fs-6">AMOUNT</th>
                        </tr>
                        </thead>
                        <tbody class=""text-center>
                        </tbody>
                    </table>
                </div>

                <div class="text-center">
                    <p class="fw-bold" style="font-size: 25px;">Thanks for your patronage</p>
                </div>
            </div>
        </div>
    </body>

</body>
</html>
