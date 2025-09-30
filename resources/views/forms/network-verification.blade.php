@extends('layouts.master')
@section('before-css')
    <link rel="stylesheet" href="{{ asset('assets/styles/vendor/pickadate/classic.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/styles/vendor/pickadate/classic.date.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/styles/vendor/datatables.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/styles/vendor/dropzone.min.css') }}">
@endsection

@section('main-content')
    <div class="breadcrumb">
        <h1>Network Prefix Verification</h1>
        <ul>
            <li><a href="">Form</a></li>
            <li>Smart Phone Verification</li>
        </ul>
    </div>

    <div class="separator-breadcrumb border-top"></div>

    <!-- Alert Messages Container -->
    <div id="alert-container" class="mb-4" style="display: none;">
        <div class="alert" role="alert" id="alert-message">
            <strong id="alert-title"></strong> <span id="alert-text"></span>
            <button type="button" class="btn-close" onclick="hideAlert()" aria-label="Close"></button>
        </div>
    </div>

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
                    <h4 class="card-title mb-3">Network Prefix Verification Table</h4>
                    <a href="{{ route('verification.export') }}" class="btn btn-secondary mb-3">Export to Excel</a>
                    <div class="table-responsive">
                        <table id="network_verification_table" class="display table table-striped table-bordered"
                            style="width:100%">
                            <thead>
                                <tr>
                                    <th>Number</th>
                                    <th>Country</th>
                                    <th>Min/Max Length</th>
                                    <th>Network</th>
                                    <th>MCC/MNC</th>
                                    <th>Live Coverage</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Ported</th>
                                    <th>Present</th>
                                    <th>Transaction ID</th>
                                    <th>Verified</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($verifications as $verification)
                                    <tr data-phone="{{ $verification->number }}">
                                        <td>{{ $verification->number }}</td>
                                        <td>{{ $verification->country_name ?? 'Unknown' }}</td>
                                        <td>{{ $verification->min_length ?? 'N/A' }}/{{ $verification->max_length ?? 'N/A' }}</td>
                                        <td>{{ $verification->network_name ?? 'Unknown' }}</td>
                                        <td>{{ $verification->mcc }}/{{ $verification->mnc }}</td>
                                        <td>
                                            @if(isset($verification->prefix))
                                                @php
                                                    $networkPrefix = App\Models\NetworkPrefix::where('prefix', $verification->prefix)->first();
                                                    $liveCoverage = $networkPrefix ? $networkPrefix->live_coverage : false;
                                                @endphp
                                                <span class="badge badge-pill badge-outline-{{ $liveCoverage ? 'success' : 'danger' }} p-2 m-1">
                                                    {{ $liveCoverage ? 'Yes' : 'No' }}
                                                </span>
                                            @else
                                                <span class="badge badge-pill badge-outline-secondary p-2 m-1">Unknown</span>
                                            @endif
                                        </td>
                                        <td>{{ ucfirst($verification->type ?? 'unknown') }}</td>
                                        <td>
                                            <span
                                                class="badge badge-pill badge-outline-{{ $verification->isSuccessful() ? 'success' : 'danger' }} p-2 m-1">
                                                {{ $verification->status_text }}
                                            </span>
                                        </td>
                                        <td>
                                            <span
                                                class="badge badge-pill badge-outline-{{ $verification->ported ? 'success' : 'secondary' }} p-2 m-1">
                                                {{ $verification->ported ? 'Yes' : 'No' }}
                                            </span>
                                        </td>
                                        <td>
                                            @php
                                                $present = strtolower($verification->present ?? 'na');
                                                if (!in_array($present, ['yes', 'no', 'na'])) {
                                                    $present = 'na';
                                                }

                                                $presentText = ucfirst($present);
                                                $presentClass = 'secondary';

                                                if ($present === 'yes') {
                                                    $presentClass = 'success';
                                                } elseif ($present === 'no') {
                                                    $presentClass = 'danger';
                                                } elseif ($present === 'na') {
                                                    $presentClass = 'secondary';
                                                }
                                            @endphp

                                            <span class="badge badge-pill badge-outline-{{ $presentClass }} p-2 m-1">
                                                {{ $presentText }}
                                            </span>
                                        </td>
                                        <td>{{ $verification->trxid ?? 'N/A' }}</td>
                                        <td>{{ $verification->created_at->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @empty
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="verify" tabindex="-1" role="dialog" aria-labelledby="verifyLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="verifyLabel">Smart Phone Number Verification</h5>
                </div>
                <div class="modal-body">
                    <form id="verifyForm">
                        @csrf
                        <div class="mb-3">
                            <label for="phone_number" class="col-form-label">Enter Phone Number</label>
                            <input type="tel" class="form-control" id="phone_number" name="phone_number"
                                placeholder="e.g., 85592313242" required>
                            <div class="form-text">Enter the full phone number including country code</div>
                            <div id="phone-validation-info" class="mt-2" style="display: none;">
                                <div class="alert mb-0" id="validation-alert">
                                    <div id="network-info">
                                        <strong>Network Detected:</strong> <span id="detected-info"></span>
                                    </div>
                                    <div id="coverage-info" class="mt-1">
                                        <strong>Live Coverage:</strong> <span id="coverage-status"></span>
                                    </div>
                                    <div id="api-recommendation" class="mt-1">
                                        <strong>API Recommendation:</strong> <span id="recommendation-text"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="invalid-feedback" id="phone-error"></div>
                        </div>
                        <div class="mb-3">
                            <label for="data_freshness" class="col-form-label">Data Freshness</label>
                            <select class="form-control" id="data_freshness" name="data_freshness">
                                <option value="">Use cached data if available (recommended)</option>
                                <option value="30">Force refresh if data is older than 30 days</option>
                                <option value="60">Force refresh if data is older than 60 days</option>
                                <option value="90">Force refresh if data is older than 90 days</option>
                                <option value="all">Always get fresh data from API</option>
                            </select>
                            <div class="form-text">Select when to fetch fresh data from the API vs using cached results</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="verifyBtn">
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                        Verify
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="upload" tabindex="-1" role="dialog" aria-labelledby="uploadLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadLabel">Batch Network Verification</h5>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="file-upload" class="form-label">Upload Excel File</label>
                        <input type="file" class="form-control" id="file-upload" accept=".xlsx,.xls,.csv" required>
                        <div class="form-text">Upload an Excel file (.xlsx, .xls) or CSV file with phone numbers in the first column</div>
                        <div class="invalid-feedback" id="batch-error"></div>
                    </div>
                    <div class="mb-3" id="file-preview" style="display: none;">
                        <h6>File Preview:</h6>
                        <div class="alert alert-info" id="preview-info"></div>
                        <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Phone Numbers Found</th>
                                    </tr>
                                </thead>
                                <tbody id="preview-body"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="batch_data_freshness" class="form-label">Data Freshness</label>
                        <select class="form-control" id="batch_data_freshness" name="batch_data_freshness">
                            <option value="">Use cached data if available (recommended)</option>
                            <option value="30">Force refresh if data is older than 30 days</option>
                            <option value="60">Force refresh if data is older than 60 days</option>
                            <option value="90">Force refresh if data is older than 90 days</option>
                            <option value="all">Always get fresh data from API</option>
                        </select>
                        <div class="form-text">Select when to fetch fresh data from the API vs using cached results</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="batchVerifyBtn">
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                        Verify All
                    </button>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <script>
        // Alert Functions (copied from existing verify.blade.php)
        function showAlert(type, title, message) {
            const alertContainer = document.getElementById('alert-container');
            const alertMessage = document.getElementById('alert-message');
            const alertTitle = document.getElementById('alert-title');
            const alertText = document.getElementById('alert-text');

            alertMessage.className = 'alert';
            alertMessage.classList.add('alert-' + type);
            alertTitle.textContent = title + '!';

            if (message.includes('\n')) {
                alertText.innerHTML = message.replace(/\n/g, '<br>');
            } else {
                alertText.textContent = message;
            }

            alertContainer.style.display = 'block';

            if (type === 'success') {
                setTimeout(() => {
                    hideAlert();
                }, 8000);
            }
        }

        function hideAlert() {
            const alertContainer = document.getElementById('alert-container');
            alertContainer.style.display = 'none';
        }

        // Update table row function
        function updateTableRow(verificationData) {
            console.log('updateTableRow called with:', verificationData);
            const table = $('#network_verification_table').DataTable();
            const phoneNumber = verificationData.phone_number || verificationData.number;

            const now = new Date();
            const formattedDate = now.getFullYear() + '-' +
                String(now.getMonth() + 1).padStart(2, '0') + '-' +
                String(now.getDate()).padStart(2, '0') + ' ' +
                String(now.getHours()).padStart(2, '0') + ':' +
                String(now.getMinutes()).padStart(2, '0');

            const statusClass = (verificationData.status === 0) ? 'success' : 'danger';
            const statusText = verificationData.status_message || 'Unknown';
            const portedClass = verificationData.ported ? 'success' : 'secondary';
            const portedText = verificationData.ported ? 'Yes' : 'No';

            // Determine live coverage status
            let liveCoverageClass = 'secondary';
            let liveCoverageText = 'Unknown';

            if (verificationData.skip_reason === 'no_live_coverage') {
                liveCoverageClass = 'danger';
                liveCoverageText = 'No';
            } else if (verificationData.prefix_info && verificationData.prefix_info.live_coverage !== undefined) {
                liveCoverageClass = verificationData.prefix_info.live_coverage ? 'success' : 'danger';
                liveCoverageText = verificationData.prefix_info.live_coverage ? 'Yes' : 'No';
            }

            const rowData = [
                phoneNumber,
                verificationData.country_name || 'Unknown',
                (verificationData.min_length || 'N/A') + '/' + (verificationData.max_length || 'N/A'),
                verificationData.network || 'Unknown',
                (verificationData.mcc || '') + '/' + (verificationData.mnc || ''),
                `<span class="badge badge-pill badge-outline-${liveCoverageClass} p-2 m-1">${liveCoverageText}</span>`,
                verificationData.type ? verificationData.type.charAt(0).toUpperCase() + verificationData.type.slice(1) : 'Unknown',
                `<span class="badge badge-pill badge-outline-${statusClass} p-2 m-1">${statusText}</span>`,
                `<span class="badge badge-pill badge-outline-${portedClass} p-2 m-1">${portedText}</span>`,
                (() => {
                    let present = verificationData.present ? verificationData.present.toLowerCase() : 'na';
                    if (!['yes', 'no', 'na'].includes(present)) {
                        present = 'na';
                    }

                    const presentText = present.charAt(0).toUpperCase() + present.slice(1);
                    let presentClass = 'secondary';

                    if (present === 'yes') {
                        presentClass = 'success';
                    } else if (present === 'no') {
                        presentClass = 'danger';
                    }

                    return `<span class="badge badge-pill badge-outline-${presentClass} p-2 m-1">${presentText}</span>`;
                })(),
                verificationData.trxid || 'N/A',
                formattedDate
            ];

            const existingRow = table.row('tr[data-phone="' + phoneNumber + '"]');

            if (existingRow.any()) {
                console.log('Found existing row for phone:', phoneNumber, '. Updating it.');
                existingRow.data(rowData).draw(false);

                const updatedNode = existingRow.node();
                $(updatedNode).css('background-color', '#fff3cd');
                setTimeout(() => {
                    $(updatedNode).css('background-color', '');
                }, 3000);

                console.log('Row updated successfully');
            } else {
                console.log('No existing row found. Adding fresh row for phone:', phoneNumber);
                const newRow = table.row.add(rowData).draw(false);

                const newNode = newRow.node();
                $(newNode).attr('data-phone', phoneNumber);

                $(newNode).css('background-color', '#e8f5e8');
                setTimeout(() => {
                    $(newNode).css('background-color', '');
                }, 3000);

                console.log('New row added successfully');
            }
        }

        // Highlight existing row for cached data
        function highlightRow(phoneNumber) {
            const table = $('#network_verification_table').DataTable();

            table.rows().every(function(rowIdx, tableLoop, rowLoop) {
                const data = this.data();
                if (data && data[0] === phoneNumber) {
                    const rowNode = this.node();

                    $(rowNode).css('background-color', '#cce5ff');
                    setTimeout(() => {
                        $(rowNode).css('background-color', '');
                    }, 2000);

                    console.log('Highlighted existing row for cached data:', phoneNumber);
                    return false;
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const verifyBtn = document.getElementById('verifyBtn');
            const phoneInput = document.getElementById('phone_number');
            const phoneError = document.getElementById('phone-error');
            const spinner = verifyBtn.querySelector('.spinner-border');
            const modal = document.getElementById('verify');

            let phoneValidationCache = {};

            // Network prefix validation (new functionality)
            async function validateNetworkPrefix(phoneNumber) {
                const validationInfo = document.getElementById('phone-validation-info');
                const validationAlert = document.getElementById('validation-alert');
                const detectedInfo = document.getElementById('detected-info');
                const coverageStatus = document.getElementById('coverage-status');
                const recommendationText = document.getElementById('recommendation-text');

                if (!phoneNumber || phoneNumber.length < 1) {
                    validationInfo.style.display = 'none';
                    phoneInput.classList.remove('is-valid', 'is-invalid');
                    return null;
                }

                const cleanNumber = phoneNumber.replace(/[^0-9]/g, '');

                if (cleanNumber.length === 0) {
                    validationInfo.style.display = 'none';
                    phoneInput.classList.remove('is-valid', 'is-invalid');
                    return null;
                }

                // Check cache first
                if (phoneValidationCache[cleanNumber]) {
                    const cachedResult = phoneValidationCache[cleanNumber];
                    displayNetworkValidationResult(cachedResult);
                    return cachedResult;
                }

                try {
                    const response = await fetch('/network-prefix/check', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content')
                        },
                        body: JSON.stringify({
                            phone_number: cleanNumber
                        })
                    });

                    const result = await response.json();
                    phoneValidationCache[cleanNumber] = result;
                    displayNetworkValidationResult(result);
                    return result;
                } catch (error) {
                    console.error('Network prefix validation error:', error);
                    validationInfo.style.display = 'none';
                    return null;
                }
            }

            function displayNetworkValidationResult(result) {
                const validationInfo = document.getElementById('phone-validation-info');
                const validationAlert = document.getElementById('validation-alert');
                const detectedInfo = document.getElementById('detected-info');
                const coverageStatus = document.getElementById('coverage-status');
                const recommendationText = document.getElementById('recommendation-text');

                if (result.success) {
                    const countryName = result.country_name || 'Unknown';
                    const networkName = result.network_name || 'Unknown';

                    detectedInfo.textContent = `${countryName} - ${networkName} (${result.prefix})`;

                    if (result.live_coverage) {
                        validationAlert.className = 'alert alert-success mb-0';
                        coverageStatus.textContent = 'Available - API call will be made';
                        recommendationText.textContent = 'This number has live coverage. Proceed with API verification.';
                    } else {
                        validationAlert.className = 'alert alert-warning mb-0';
                        coverageStatus.textContent = 'Not Available - API call will be skipped';
                        recommendationText.textContent = 'This number has no live coverage. API verification will be skipped to save costs.';
                    }

                    phoneInput.classList.remove('is-invalid');
                    phoneInput.classList.add('is-valid');
                    validationInfo.style.display = 'block';
                } else {
                    validationAlert.className = 'alert alert-danger mb-0';
                    detectedInfo.textContent = 'Network prefix not found in database';
                    coverageStatus.textContent = 'Unknown';
                    recommendationText.textContent = 'Please check the phone number format or ensure the prefix exists in database.';
                    validationInfo.style.display = 'block';
                    phoneInput.classList.remove('is-valid');
                    phoneInput.classList.add('is-invalid');
                    phoneError.textContent = '';  // Don't duplicate the error message
                }
            }

            // Add real-time validation to phone input
            phoneInput.addEventListener('input', function(e) {
                phoneError.textContent = '';

                clearTimeout(phoneInput.validationTimeout);
                phoneInput.validationTimeout = setTimeout(() => {
                    validateNetworkPrefix(e.target.value);
                }, 500);
            });

            verifyBtn.addEventListener('click', async function() {
                const phoneNumber = phoneInput.value.trim();
                const dataFreshness = document.getElementById('data_freshness').value;

                if (!phoneNumber) {
                    phoneInput.classList.add('is-invalid');
                    phoneError.textContent = 'Please enter a phone number';
                    return;
                }

                phoneInput.classList.remove('is-invalid');
                phoneError.textContent = '';

                verifyBtn.disabled = true;
                spinner.classList.remove('d-none');
                verifyBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Validating...';

                // Validate network prefix first
                const validationResult = await validateNetworkPrefix(phoneNumber);

                if (!validationResult || !validationResult.success) {
                    phoneInput.classList.add('is-invalid');
                    phoneError.textContent = validationResult?.error || 'Network prefix not found';
                    showAlert('danger', 'Validation Failed', validationResult?.error || 'Network prefix not found in database.');

                    verifyBtn.disabled = false;
                    spinner.classList.add('d-none');
                    verifyBtn.innerHTML = 'Verify';
                    return;
                }

                // Check if phone has live coverage
                if (!validationResult.live_coverage) {
                    const proceed = confirm(
                        'This phone number has no live coverage according to our network prefix database. ' +
                        'API verification will be skipped to save costs. ' +
                        'Do you want to proceed with local validation only?'
                    );

                    if (!proceed) {
                        verifyBtn.disabled = false;
                        spinner.classList.add('d-none');
                        verifyBtn.innerHTML = 'Verify';
                        return;
                    }
                }

                verifyBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Verifying...';

                // Make API call to new network prefix service
                fetch('{{ route("network-prefix.verify") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content')
                        },
                        body: JSON.stringify({
                            phone_number: phoneNumber,
                            data_freshness: dataFreshness
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            $(modal).modal('hide');

                            if (data.cached) {
                                showAlert('info', 'Cached Result', 'Phone number verification retrieved from cache.');
                                highlightRow(data.data.phone_number || data.data.number);
                            } else {
                                let successMessage = 'Phone number verified successfully!';
                                if (data.data.skip_reason === 'no_live_coverage') {
                                    successMessage = 'Phone number processed - no live coverage, API call skipped to save costs.';
                                }
                                showAlert('success', 'Success', successMessage);
                                updateTableRow(data.data);
                            }
                        } else {
                            phoneInput.classList.add('is-invalid');
                            phoneError.textContent = data.error || 'Verification failed';
                            showAlert('warning', 'Warning', data.error || 'Phone number verification failed.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        phoneInput.classList.add('is-invalid');
                        phoneError.textContent = 'Network error. Please try again.';
                        showAlert('danger', 'Error', 'Network error occurred. Please try again.');
                    })
                    .finally(() => {
                        verifyBtn.disabled = false;
                        spinner.classList.add('d-none');
                        verifyBtn.innerHTML = 'Verify';
                    });
            });

            // Modal reset functionality
            $(modal).on('hidden.bs.modal', function() {
                phoneInput.value = '';
                document.getElementById('data_freshness').value = '';
                phoneInput.classList.remove('is-invalid', 'is-valid');
                phoneError.textContent = '';
                document.getElementById('phone-validation-info').style.display = 'none';
                verifyBtn.disabled = false;
                spinner.classList.add('d-none');
                verifyBtn.innerHTML = 'Verify';
            });

            phoneInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    verifyBtn.click();
                }
            });

            // Batch verification functionality (copied and adapted)
            const batchVerifyBtn = document.getElementById('batchVerifyBtn');
            const fileUpload = document.getElementById('file-upload');
            const batchError = document.getElementById('batch-error');
            const batchModal = document.getElementById('upload');
            const filePreview = document.getElementById('file-preview');
            const previewInfo = document.getElementById('preview-info');
            const previewBody = document.getElementById('preview-body');
            let extractedPhoneNumbers = [];

            // File upload handlers and batch processing (same as original)
            fileUpload.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;

                const reader = new FileReader();
                reader.onload = function(event) {
                    try {
                        if (file.name.endsWith('.csv')) {
                            extractedPhoneNumbers = parseCSV(event.target.result);
                        } else {
                            extractedPhoneNumbers = parseExcel(event.target.result);
                        }
                        showPreview(extractedPhoneNumbers);
                    } catch (error) {
                        fileUpload.classList.add('is-invalid');
                        batchError.textContent = 'Error reading file: ' + error.message;
                    }
                };

                if (file.name.endsWith('.csv')) {
                    reader.readAsText(file);
                } else {
                    reader.readAsArrayBuffer(file);
                }
            });

            function parseCSV(csvText) {
                const lines = csvText.split('\n');
                const phoneNumbers = [];

                for (let line of lines) {
                    const columns = line.split(',');
                    if (columns[0] && columns[0].trim()) {
                        const phone = columns[0].trim().replace(/[^\d+]/g, '');
                        if (phone && phone.length >= 8) {
                            phoneNumbers.push(phone);
                        }
                    }
                }
                return phoneNumbers;
            }

            function parseExcel(arrayBuffer) {
                const workbook = XLSX.read(arrayBuffer, {type: 'array'});
                const firstSheetName = workbook.SheetNames[0];
                const worksheet = workbook.Sheets[firstSheetName];
                const jsonData = XLSX.utils.sheet_to_json(worksheet, {header: 1});
                const phoneNumbers = [];

                for (let row of jsonData) {
                    if (row[0]) {
                        const phone = String(row[0]).trim().replace(/[^\d+]/g, '');
                        if (phone && phone.length >= 8) {
                            phoneNumbers.push(phone);
                        }
                    }
                }

                return phoneNumbers;
            }

            function showPreview(phoneNumbers) {
                if (phoneNumbers.length === 0) {
                    fileUpload.classList.add('is-invalid');
                    batchError.textContent = 'No valid phone numbers found in file';
                    filePreview.style.display = 'none';
                    return;
                }

                fileUpload.classList.remove('is-invalid');
                batchError.textContent = '';
                previewInfo.textContent = `Found ${phoneNumbers.length} phone numbers`;
                previewBody.innerHTML = '';

                phoneNumbers.slice(0, 10).forEach(phone => {
                    const row = document.createElement('tr');
                    row.innerHTML = `<td>${phone}</td>`;
                    previewBody.appendChild(row);
                });

                if (phoneNumbers.length > 10) {
                    const row = document.createElement('tr');
                    row.innerHTML = `<td><em>... and ${phoneNumbers.length - 10} more</em></td>`;
                    previewBody.appendChild(row);
                }

                filePreview.style.display = 'block';
            }

            batchVerifyBtn.addEventListener('click', function() {
                if (extractedPhoneNumbers.length === 0) {
                    fileUpload.classList.add('is-invalid');
                    batchError.textContent = 'Please upload a file with phone numbers';
                    return;
                }

                fileUpload.classList.remove('is-invalid');
                batchError.textContent = '';

                batchVerifyBtn.disabled = true;
                const batchSpinner = batchVerifyBtn.querySelector('.spinner-border');
                batchSpinner.classList.remove('d-none');
                batchVerifyBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Verifying...';

                fetch('{{ route("network-prefix.batch") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            phone_numbers: extractedPhoneNumbers,
                            data_freshness: document.getElementById('batch_data_freshness').value
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            $(batchModal).modal('hide');

                            let successMessage = `Processed ${data.processed} numbers, ${data.saved} successful verifications.`;
                            if (data.skipped_no_coverage > 0) {
                                successMessage += `\n${data.skipped_no_coverage} numbers skipped (no live coverage).`;
                            }
                            if (data.cache_message) {
                                successMessage += `\n\n${data.cache_message}`;
                            }

                            showAlert('success', 'Batch Verification Complete', successMessage);

                            if (data.data && data.data.length > 0) {
                                data.data.forEach(verification => {
                                    if (verification.source !== 'cache') {
                                        updateTableRow(verification);
                                    }
                                });
                            }
                        } else {
                            fileUpload.classList.add('is-invalid');
                            batchError.textContent = data.error || 'Batch verification failed';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        fileUpload.classList.add('is-invalid');
                        batchError.textContent = 'Network error. Please try again.';
                        showAlert('danger', 'Error', 'Network error occurred during batch verification. Please try again.');
                    })
                    .finally(() => {
                        batchVerifyBtn.disabled = false;
                        batchSpinner.classList.add('d-none');
                        batchVerifyBtn.innerHTML = 'Verify All';
                    });
            });

            $(batchModal).on('hidden.bs.modal', function() {
                fileUpload.value = '';
                document.getElementById('batch_data_freshness').value = '';
                fileUpload.classList.remove('is-invalid');
                batchError.textContent = '';
                filePreview.style.display = 'none';
                extractedPhoneNumbers = [];
                batchVerifyBtn.disabled = false;
                const batchSpinner = batchVerifyBtn.querySelector('.spinner-border');
                batchSpinner.classList.add('d-none');
                batchVerifyBtn.innerHTML = 'Verify All';
            });
        });
    </script>
@endsection