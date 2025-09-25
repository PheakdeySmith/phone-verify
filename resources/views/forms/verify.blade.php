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
                                    <th>Phone Number</th>
                                    <th>Current Network</th>
                                    <th>Origin Network</th>
                                    <th>Status</th>
                                    <th>Type</th>
                                    <th>Ported</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($verificationResults as $result)
                                    <tr>
                                        <td>{{ $result->phone_number }}</td>
                                        <td>{{ $result->current_network_name ?? 'Unknown' }}</td>
                                        <td>{{ $result->origin_network_name ?? 'Unknown' }}</td>
                                        <td>
                                            <span
                                                class="badge badge-pill badge-outline-{{ $result->status == 0 ? 'success' : 'danger' }} p-2 m-1">{{ $result->status_message }}</span>
                                        </td>
                                        <td>{{ ucfirst($result->type ?? 'unknown') }}</td>
                                        <td>
                                            <span
                                                class="badge badge-pill badge-outline-{{ $result->ported ? 'success' : 'danger' }} p-2 m-1">
                                                {{ $result->ported ? 'Yes' : 'No' }}
                                            </span>
                                        </td>

                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No verification results found!</td>
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
                        <label for="phone_number" class="col-form-label">Enter Phone Number</label>
                        <input type="tel" class="form-control" id="phone_number" name="phone_number" required>
                        <div class="invalid-feedback" id="phone-error"></div>
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
                        <div class="form-text">Upload an Excel file (.xlsx, .xls) or CSV file with phone numbers in the first column</div>
                        <div class="invalid-feedback" id="batch-error"></div>
                    </div>
                    <div class="mb-3" id="file-preview" style="display: none;">
                        <h6>File Preview:</h6>
                        <div class="alert alert-info" id="preview-info"></div>
                        <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                            <table class="table table-sm">
                                <thead>
                                    <tr><th>Phone Numbers Found</th></tr>
                                </thead>
                                <tbody id="preview-body"></tbody>
                            </table>
                        </div>
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
            alertText.textContent = message;

            alertContainer.style.display = 'block';

            // Auto-hide success messages after 5 seconds
            if (type === 'success') {
                setTimeout(() => {
                    hideAlert();
                }, 5000);
            }
        }

        function hideAlert() {
            const alertContainer = document.getElementById('alert-container');
            alertContainer.style.display = 'none';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const verifyBtn = document.getElementById('verifyBtn');
            const phoneInput = document.getElementById('phone_number');
            const phoneError = document.getElementById('phone-error');
            const spinner = verifyBtn.querySelector('.spinner-border');
            const modal = document.getElementById('verify');

            verifyBtn.addEventListener('click', function() {
                const phoneNumber = phoneInput.value.trim();

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
                verifyBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Verifying...';

                // Make API call
                fetch('{{ route("verification.verify") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        phone_number: phoneNumber
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        $(modal).modal('hide');
                        showAlert('success', 'Success', 'Phone number verified successfully!');
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
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

            $(modal).on('hidden.bs.modal', function() {
                phoneInput.value = '';
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
                const workbook = XLSX.read(arrayBuffer, { type: 'array' });
                const firstSheetName = workbook.SheetNames[0];
                const worksheet = workbook.Sheets[firstSheetName];

                const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });

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
                batchVerifyBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Verifying...';

                // Make batch API call
                fetch('{{ route("verification.batch") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        phone_numbers: extractedPhoneNumbers
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Success - close modal, show success alert and reload page
                        $(batchModal).modal('hide');
                        showAlert('success', 'Success', `Processed ${data.processed} numbers, saved ${data.saved} results.`);
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
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
                    showAlert('danger', 'Error', 'Network error occurred during batch verification. Please try again.');
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
