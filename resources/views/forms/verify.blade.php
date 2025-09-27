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
                    <h4 class="card-title mb-3">Phone Number Table</h4>
                    <a href="{{ route('verification.export') }}" class="btn btn-secondary mb-3">Export to Excel</a>
                    <div class="table-responsive">
                        <table id="deafult_ordering_table" class="display table table-striped table-bordered"
                            style="width:100%">
                            {{-- @include('datatables.table_content') --}}

                            <thead>
                                <tr>
                                    <th>Number</th>
                                    <th>Network</th>
                                    <th>MCC/MNC</th>
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
                                        <td>{{ $verification->network ?? 'Unknown' }}</td>
                                        <td>{{ $verification->mcc }}/{{ $verification->mnc }}</td>
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
                                                $presentText = ucfirst($verification->present ?? 'N/A');
                                                $presentClass = 'secondary'; // Default color (gray) for N/A or other values

                                                if ($verification->present === 'yes') {
                                                    $presentClass = 'info'; // Blue for 'yes'
                                                } elseif ($verification->present === 'no') {
                                                    $presentClass = 'warning'; // Orange for 'no'
                                                } elseif ($verification->present === 'na') {
                                                    $presentClass = 'danger'; // Red for 'na' (Not available)
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
                                    <tr>
                                        <td colspan="9" class="text-center">No verification results found!</td>
                                    </tr>
                                @endforelse
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
                    <h5 class="modal-title" id="verifyLabel">Verify Phone Number</h5>
                </div>
                <div class="modal-body">
                    <form id="verifyForm">
                        @csrf
                        <div class="mb-3">
                            <label for="phone_number" class="col-form-label">Enter Phone Number</label>
                            <input type="tel" class="form-control" id="phone_number" name="phone_number" required>
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
                            <div class="form-text">Select when to fetch fresh data from the API vs using cached results
                            </div>
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
                    <h5 class="modal-title" id="uploadLabel">Batch Verification</h5>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="file-upload" class="form-label">Upload Excel File</label>
                        <input type="file" class="form-control" id="file-upload" accept=".xlsx,.xls,.csv" required>
                        <div class="form-text">Upload an Excel file (.xlsx, .xls) or CSV file with phone numbers in the
                            first column</div>
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
        // Alert Functions
        function showAlert(type, title, message) {
            const alertContainer = document.getElementById('alert-container');
            const alertMessage = document.getElementById('alert-message');
            const alertTitle = document.getElementById('alert-title');
            const alertText = document.getElementById('alert-text');

            // Remove existing alert classes
            alertMessage.className = 'alert';

            // Add new alert type class
            alertMessage.classList.add('alert-' + type);

            alertTitle.textContent = title + '!';

            // Handle multi-line messages by replacing \n with <br>
            if (message.includes('\n')) {
                alertText.innerHTML = message.replace(/\n/g, '<br>');
            } else {
                alertText.textContent = message;
            }

            alertContainer.style.display = 'block';

            // Auto-hide success messages after 8 seconds (increased for cache stats)
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

        // assets/js/your-script-file.js

        function addOrUpdateVerificationInTable(verificationData) {
            const table = $('#deafult_ordering_table').DataTable();
            const phoneNumber = verificationData.phone_number || verificationData.number;

            // --- Prepare the new row's data ---
            const now = new Date();
            const formattedDate = now.getFullYear() + '-' +
                String(now.getMonth() + 1).padStart(2, '0') + '-' +
                String(now.getDate()).padStart(2, '0') + ' ' +
                String(now.getHours()).padStart(2, '0') + ':' +
                String(now.getMinutes()).padStart(2, '0');

            const statusClass = (verificationData.status === 0 && verificationData.error === 0) ? 'success' : 'danger';
            const statusText = verificationData.status_message || 'Unknown';
            const portedClass = verificationData.ported ? 'success' : 'secondary';
            const portedText = verificationData.ported ? 'Yes' : 'No';

            const rowData = [
                phoneNumber,
                verificationData.network || 'Unknown',
                (verificationData.mcc || '') + '/' + (verificationData.mnc || ''),
                verificationData.type ? verificationData.type.charAt(0).toUpperCase() + verificationData.type.slice(1) :
                'Unknown',
                `<span class="badge badge-pill badge-outline-${statusClass} p-2 m-1">${statusText}</span>`,
                `<span class="badge badge-pill badge-outline-${portedClass} p-2 m-1">${portedText}</span>`,
                `<span class="badge badge-pill badge-outline-info p-2 m-1">${verificationData.present ? verificationData.present.charAt(0).toUpperCase() + verificationData.present.slice(1) : 'N/A'}</span>`,
                verificationData.trxid || 'N/A',
                formattedDate
            ];

            // --- New, more efficient logic to find and update/add the row ---
            const existingRow = table.row('tr[data-phone="' + phoneNumber + '"]');

            if (existingRow.any()) {
                // --- Row EXISTS, so UPDATE it ---
                console.log('Found existing row for phone:', phoneNumber, '. Updating it.');
                existingRow.data(rowData).draw(false); // Update data and redraw

                const updatedNode = existingRow.node();
                $(updatedNode).css('background-color', '#fff3cd'); // Yellow highlight for update
                setTimeout(() => {
                    $(updatedNode).css('background-color', '');
                }, 3000);

                console.log('Row updated successfully');

            } else {
                // --- Row does NOT exist, so ADD it ---
                console.log('No existing row found. Adding fresh row for phone:', phoneNumber);
                const newRow = table.row.add(rowData).draw(false); // Add and redraw

                // Set the data-phone attribute on the new <tr> for future lookups
                const newNode = newRow.node();
                $(newNode).attr('data-phone', phoneNumber);

                $(newNode).css('background-color', '#e8f5e8'); // Green highlight for new
                setTimeout(() => {
                    $(newNode).css('background-color', '');
                }, 3000);

                console.log('New row added successfully');
            }
        }

        // Function to highlight existing row for cached data
        function highlightExistingRow(phoneNumber) {
            const table = $('#deafult_ordering_table').DataTable();

            table.rows().every(function(rowIdx, tableLoop, rowLoop) {
                const data = this.data();
                if (data && data[0] === phoneNumber) {
                    const rowNode = this.node();

                    // Add blue highlight for cached data
                    $(rowNode).css('background-color', '#cce5ff');
                    setTimeout(() => {
                        $(rowNode).css('background-color', '');
                    }, 2000);

                    console.log('Highlighted existing row for cached data:', phoneNumber);
                    return false; // Break the loop
                }
            });
        }

        // Function to clean up any existing duplicates in the table
        function cleanupDuplicates() {
            const table = $('#deafult_ordering_table').DataTable();
            const phoneNumbers = new Set();
            const rowsToRemove = [];

            // Find duplicate rows
            table.rows().every(function(rowIdx, tableLoop, rowLoop) {
                const data = this.data();
                if (data && data[0]) {
                    const phoneNumber = data[0];
                    if (phoneNumbers.has(phoneNumber)) {
                        // This is a duplicate, mark for removal
                        rowsToRemove.push(rowIdx);
                        console.log('Found duplicate row for phone:', phoneNumber, 'at index:', rowIdx);
                    } else {
                        phoneNumbers.add(phoneNumber);
                    }
                }
            });

            // Remove duplicates (in reverse order to maintain correct indices)
            for (let i = rowsToRemove.length - 1; i >= 0; i--) {
                table.row(rowsToRemove[i]).remove();
                console.log('Removed duplicate row at index:', rowsToRemove[i]);
            }

            if (rowsToRemove.length > 0) {
                table.draw(false);
                console.log('Cleaned up', rowsToRemove.length, 'duplicate rows');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Clean up any existing duplicates when page loads
            setTimeout(() => {
                cleanupDuplicates();
            }, 500); // Wait for DataTable to fully initialize

            const verifyBtn = document.getElementById('verifyBtn');
            const phoneInput = document.getElementById('phone_number');
            const phoneError = document.getElementById('phone-error');
            const spinner = verifyBtn.querySelector('.spinner-border');
            const modal = document.getElementById('verify');

            verifyBtn.addEventListener('click', function() {
                const phoneNumber = phoneInput.value.trim();
                const dataFreshness = document.getElementById('data_freshness').value;

                if (!phoneNumber) {
                    phoneInput.classList.add('is-invalid');
                    phoneError.textContent = 'Please enter a phone number';
                    return;
                }

                // Remove previous error state
                phoneInput.classList.remove('is-invalid');
                phoneError.textContent = '';

                // Show loading state
                verifyBtn.disabled = true;
                spinner.classList.remove('d-none');
                verifyBtn.innerHTML =
                    '<span class="spinner-border spinner-border-sm" role="status"></span> Verifying...';

                // Make API call
                fetch('{{ route('verification.verify') }}', {
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

                            // Check if this is cached data
                            if (data.cached) {
                                showAlert('info', 'Cached Result',
                                    'Phone number verification retrieved from cache.');
                                console.log('Data retrieved from cache, not updating table');

                                // Just highlight the existing row if it exists
                                highlightExistingRow(data.data.phone_number || data.data.number);
                            } else {
                                showAlert('success', 'Success', 'Phone number verified successfully!');
                                // Only add/update table for fresh API data
                                addOrUpdateVerificationInTable(data.data);
                            }
                        } else {
                            phoneInput.classList.add('is-invalid');
                            phoneError.textContent = data.error || 'Verification failed';
                            showAlert('warning', 'Warning', data.error ||
                                'Phone number verification failed.');
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

            $(modal).on('hidden.bs.modal', function() {
                phoneInput.value = '';
                document.getElementById('data_freshness').value = '';
                phoneInput.classList.remove('is-invalid');
                phoneError.textContent = '';
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

            const batchVerifyBtn = document.getElementById('batchVerifyBtn');
            const fileUpload = document.getElementById('file-upload');
            const batchError = document.getElementById('batch-error');
            const batchModal = document.getElementById('upload');
            const filePreview = document.getElementById('file-preview');
            const previewInfo = document.getElementById('preview-info');
            const previewBody = document.getElementById('preview-body');
            let extractedPhoneNumbers = [];

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
                const workbook = XLSX.read(arrayBuffer, {
                    type: 'array'
                });
                const firstSheetName = workbook.SheetNames[0];
                const worksheet = workbook.Sheets[firstSheetName];

                const jsonData = XLSX.utils.sheet_to_json(worksheet, {
                    header: 1
                });

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

                // Remove previous error state
                fileUpload.classList.remove('is-invalid');
                batchError.textContent = '';

                // Show loading state
                batchVerifyBtn.disabled = true;
                const batchSpinner = batchVerifyBtn.querySelector('.spinner-border');
                batchSpinner.classList.remove('d-none');
                batchVerifyBtn.innerHTML =
                    '<span class="spinner-border spinner-border-sm" role="status"></span> Verifying...';

                // Make batch API call
                fetch('{{ route('verification.batch') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content')
                        },
                        body: JSON.stringify({
                            phone_numbers: extractedPhoneNumbers,
                            data_freshness: document.getElementById('batch_data_freshness')
                                .value
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Success - close modal, show success alert and add data to table
                            $(batchModal).modal('hide');

                            // Create detailed success message with cache statistics
                            let successMessage =
                                `Processed ${data.processed} numbers, ${data.saved} successful verifications.`;
                            if (data.cache_message) {
                                successMessage += `\n\n${data.cache_message}`;
                            }

                            showAlert('success', 'Batch Verification Complete', successMessage);

                            // Add or update each verification in the table (only for fresh data)
                            if (data.data && data.data.length > 0) {
                                data.data.forEach(verification => {
                                    // Only update table if this is fresh data (not cached)
                                    if (verification.source !== 'cache') {
                                        addOrUpdateVerificationInTable(verification);
                                    }
                                });
                            }
                        } else {
                            // Show error
                            fileUpload.classList.add('is-invalid');
                            batchError.textContent = data.error || 'Batch verification failed';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        fileUpload.classList.add('is-invalid');
                        batchError.textContent = 'Network error. Please try again.';
                        showAlert('danger', 'Error',
                            'Network error occurred during batch verification. Please try again.');
                    })
                    .finally(() => {
                        // Reset button state
                        batchVerifyBtn.disabled = false;
                        batchSpinner.classList.add('d-none');
                        batchVerifyBtn.innerHTML = 'Verify All';
                    });
            });

            // Reset batch form when modal is hidden
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
