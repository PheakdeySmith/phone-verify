@extends('layouts.master')
@section('before-css')
    <link rel="stylesheet" href="{{ asset('assets/styles/vendor/pickadate/classic.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/styles/vendor/pickadate/classic.date.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/styles/vendor/datatables.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/styles/vendor/dropzone.min.css') }}">
@endsection

@section('main-content')
    <div class="breadcrumb">
        <h1>Verification</h1>
        <ul>
            <li><a href="">Form</a></li>
            <li>Verify Phone Numbers</li>
        </ul>
    </div>

    <div class="separator-breadcrumb border-top"></div>
    <div class="col-md-3">
        <div class="card mb-4">
            <div class="card-body">
                <div class="card-title">Actions</div>
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#verify">
                    Enter Number
                </button>
                <button type="button" class="btn btn-secondary" data-toggle="modal" data-target="#upload">
                    Import Files
                </button>
            </div>
        </div>
    </div>

    <div class="row">

        <div class="col-md-12 mb-4">
            <div class="card text-start">

                <div class="card-body">
                    <h4 class="card-title mb-3">Phone Number Table</h4>
                    <button id="download-csv-btn" class="btn btn-secondary mb-3">Download as CSV</button>
                    <div class="table-responsive">
                        <table id="deafult_ordering_table" class="display table table-striped table-bordered"
                            style="width:100%">
                            {{-- @include('datatables.table_content') --}}

                            <thead>
                                <tr>
                                    <th>Phone Number</th>
                                    <th>Current Network</th>
                                    <th>Origin Network</th>
                                    <th>Status</th>
                                    <th>Type</th>
                                    <th>Ported</th>
                                    <th>Ported Date</th>
                                    <th>Roaming</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $phoneData = [
                                        '40721987086' => [
                                            'current_network' => [
                                                'lrn' => null,
                                                'mcc' => 226,
                                                'mnc' => 10,
                                                'name' => 'Orange Romania',
                                                'spid' => 4018740,
                                            ],
                                            'is_roaming' => false,
                                            'number' => 40721987086,
                                            'origin_network' => [
                                                'mcc' => 226,
                                                'mnc' => 1,
                                                'name' => 'Vodafone Romania',
                                                'spid' => 4018720,
                                            ],
                                            'ported' => true,
                                            'ported_date' => '2016-12-30 15:15:19',
                                            'ported_date_type' => 'exact',
                                            'present' => 'yes',
                                            'status' => 0,
                                            'status_message' => 'Success',
                                            'type' => 'mobile',
                                            'etype' => 10,
                                        ],
                                        '40723456789' => [
                                            'current_network' => [
                                                'lrn' => null,
                                                'mcc' => 226,
                                                'mnc' => 1,
                                                'name' => 'Vodafone Romania',
                                                'spid' => 4018720,
                                            ],
                                            'is_roaming' => false,
                                            'number' => 40723456789,
                                            'origin_network' => [
                                                'mcc' => 226,
                                                'mnc' => 1,
                                                'name' => 'Vodafone Romania',
                                                'spid' => 4018720,
                                            ],
                                            'ported' => false,
                                            'ported_date' => null,
                                            'ported_date_type' => null,
                                            'present' => 'yes',
                                            'status' => 0,
                                            'status_message' => 'Success',
                                            'type' => 'mobile',
                                            'etype' => 10,
                                        ],
                                        '40731234567' => [
                                            'current_network' => [
                                                'lrn' => null,
                                                'mcc' => 226,
                                                'mnc' => 3,
                                                'name' => 'Telekom Romania',
                                                'spid' => 4018730,
                                            ],
                                            'is_roaming' => true,
                                            'number' => 40731234567,
                                            'origin_network' => [
                                                'mcc' => 226,
                                                'mnc' => 10,
                                                'name' => 'Orange Romania',
                                                'spid' => 4018740,
                                            ],
                                            'ported' => true,
                                            'ported_date' => '2019-05-14 09:30:45',
                                            'ported_date_type' => 'exact',
                                            'present' => 'yes',
                                            'status' => 0,
                                            'status_message' => 'Success',
                                            'type' => 'mobile',
                                            'etype' => 10,
                                        ],
                                        '40745678901' => [
                                            'current_network' => [
                                                'lrn' => null,
                                                'mcc' => 226,
                                                'mnc' => 15,
                                                'name' => 'Digi Romania',
                                                'spid' => 4018750,
                                            ],
                                            'is_roaming' => false,
                                            'number' => 40745678901,
                                            'origin_network' => [
                                                'mcc' => 226,
                                                'mnc' => 15,
                                                'name' => 'Digi Romania',
                                                'spid' => 4018750,
                                            ],
                                            'ported' => false,
                                            'ported_date' => null,
                                            'ported_date_type' => null,
                                            'present' => 'yes',
                                            'status' => 0,
                                            'status_message' => 'Success',
                                            'type' => 'mobile',
                                            'etype' => 10,
                                        ],
                                        '40756789012' => [
                                            'current_network' => [
                                                'lrn' => null,
                                                'mcc' => 226,
                                                'mnc' => 10,
                                                'name' => 'Orange Romania',
                                                'spid' => 4018740,
                                            ],
                                            'is_roaming' => false,
                                            'number' => 40756789012,
                                            'origin_network' => [
                                                'mcc' => 226,
                                                'mnc' => 3,
                                                'name' => 'Telekom Romania',
                                                'spid' => 4018730,
                                            ],
                                            'ported' => true,
                                            'ported_date' => '2021-11-08 14:22:33',
                                            'ported_date_type' => 'exact',
                                            'present' => 'yes',
                                            'status' => 0,
                                            'status_message' => 'Success',
                                            'type' => 'mobile',
                                            'etype' => 10,
                                        ],
                                    ];
                                @endphp

                                @foreach ($phoneData as $phoneNumber => $data)
                                    <tr>
                                        <td>{{ $phoneNumber }}</td>
                                        <td>{{ $data['current_network']['name'] }}</td>
                                        <td>{{ $data['origin_network']['name'] }}</td>
                                        <td>
                                            <span
                                                class="badge badge-pill badge-outline-{{ $data['status'] == 0 ? 'success' : 'danger' }} p-2 m-1">{{ $data['status_message'] }}</span>
                                        </td>
                                        <td>{{ ucfirst($data['type']) }}</td>
                                        <td>
                                            <span
                                                class="badge badge-pill badge-outline-{{ $data['ported'] ? 'success' : 'danger' }} p-2 m-1">
                                                {{ $data['ported'] ? 'Yes' : 'No' }}
                                            </span>
                                        </td>
                                        <td>{{ $data['ported_date'] ?? 'N/A' }}</td>
                                        <td>
                                            <span
                                                class="badge badge-pill badge-outline-{{ $data['is_roaming'] ? 'success' : 'danger' }} p-2 m-1">
                                                {{ $data['is_roaming'] ? 'Yes' : 'No' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
        <!-- end of col -->

    </div>

    <!-- Modal -->
    <div class="modal fade" id="verify" tabindex="-1" role="dialog" aria-labelledby="verifyLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="verifyLabel">Modal title</h5>
                </div>
                <div class="modal-body">
                    <label for="number" class="col-form-label">Enter Phone Number</label>
                    <input type="phone" class="form-control" id="number">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Verify</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="upload" tabindex="-1" role="dialog" aria-labelledby="uploadLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadLabel">Upload CVS Files</h5>
                </div>
                <div class="modal-body">
                    <form action="#" class="dropzone" id="single-file-upload">
                        <div class="fallback">
                            <input name="file" type="file" />
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Verify</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-js')
    <script src="{{ asset('assets/js/vendor/pickadate/picker.js') }}"></script>
    <script src="{{ asset('assets/js/vendor/pickadate/picker.date.js') }}"></script>
@endsection

@section('bottom-js')
    <script src="{{ asset('assets/js/form.basic.script.js') }}"></script>
    <script src="{{ asset('assets/js/modal.script.js') }}"></script>
    <script src="{{ asset('assets/js/vendor/datatables.min.js') }}"></script>
    <script src="{{ asset('assets/js/datatables.script.js') }}"></script>
    <script src="{{ asset('assets/js/vendor/dropzone.min.js') }}"></script>
    <script src="{{ asset('assets/js/dropzone.script.js') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('download-csv-btn').addEventListener('click', function() {
                const table = document.getElementById('deafult_ordering_table');
                let csv = [];

                // Get table headers
                const headers = Array.from(table.querySelectorAll('thead th')).map(th => {
                    return th.innerText.trim().replace(/\s+/g, ' ');
                });
                csv.push(headers.join(','));

                // Get all table rows from tbody
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const rowData = [];
                    const cells = row.querySelectorAll('td');

                    cells.forEach(cell => {
                        let cellText = '';
                        // Check if cell contains a span (badge) and get its text
                        const span = cell.querySelector('span');
                        if (span) {
                            cellText = span.innerText.trim();
                        } else {
                            cellText = cell.innerText.trim();
                        }

                        // Escape quotes and wrap in quotes if necessary
                        cellText = cellText.replace(/"/g, '""');
                        if (cellText.includes(',') || cellText.includes('\n') || cellText.includes('"')) {
                            cellText = `"${cellText}"`;
                        }
                        rowData.push(cellText);
                    });

                    csv.push(rowData.join(','));
                });

                // Create Excel-compatible CSV content with BOM for UTF-8
                const BOM = '\uFEFF';
                const csvContent = BOM + csv.join('\r\n');

                // Create a Blob and trigger the download
                const csvFile = new Blob([csvContent], {
                    type: 'text/csv;charset=utf-8;'
                });
                const downloadLink = document.createElement('a');
                downloadLink.href = URL.createObjectURL(csvFile);
                downloadLink.setAttribute('download', 'phone_number_data.csv');
                downloadLink.style.display = 'none';
                document.body.appendChild(downloadLink);
                downloadLink.click();
                document.body.removeChild(downloadLink);
            });
        });
    </script>
@endsection
