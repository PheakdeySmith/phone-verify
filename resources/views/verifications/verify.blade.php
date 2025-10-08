@extends('layouts.master')
@section('before-css')
    <link rel="stylesheet" href="{{ asset('assets/styles/vendor/pickadate/classic.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/styles/vendor/pickadate/classic.date.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/styles/vendor/datatables.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/styles/vendor/dropzone.min.css') }}">
@endsection

@section('main-content')
    <div class="breadcrumb d-flex justify-content-between align-items-center">
        <div>
            <h1>Phone Number</h1>
            <ul class="mb-0">
                <li><a href="">Form</a></li>
                <li>Verification</li>
            </ul>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-secondary" data-toggle="modal" data-target="#filterModal" id="filterBtn">
                <i class="nav-icon me-2 i-Filter-2"></i> <span id="filterCount" class="badge badge-light d-none">0</span>
            </button>
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#verify">
                <i class="fas fa-plus"></i> Enter Number
            </button>
            <button type="button" class="btn btn-secondary" data-toggle="modal" data-target="#upload">
                <i class="fas fa-upload"></i> Import Files
            </button>
        </div>
    </div>

    <div class="separator-breadcrumb border-top"></div>

    <!-- Alert Messages Container -->
    <div id="alert-container" class="mb-4" style="display: none;">
        <div class="alert" role="alert" id="alert-message">
            <strong id="alert-title"></strong> <span id="alert-text"></span>
            <button type="button" class="btn-close" onclick="hideAlert()" aria-label="Close"></button>
        </div>
    </div>

    <!-- Verification Result Card Container -->
    <div id="verification-result-card" class="mb-4" style="display: none;">
        <!-- Card content will be dynamically inserted here -->
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0"><i class="fas fa-table"></i> Network Prefix Verification Table</h4>
                        <a href="{{ route('verification.export') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-download"></i> Export to Excel
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table id="network_verification_table" class="display table table-striped table-bordered"
                            style="width:100%">
                            <thead>
                                <tr>
                                    <th>Number</th>
                                    <th>Country</th>
                                    <th>Network</th>
                                    <th>MCC/MNC</th>
                                    <th>Live Coverage</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Status Message</th>
                                    <th>Ported</th>
                                    <th>Present</th>
                                    <th>Verified</th>
                                </tr>
                            </thead>
                            <tbody id="verifications-tbody">
                                <!-- Data will be loaded via API -->
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
                            <option value="all">Verify All Fresh (bypass cache & database)</option>
                        </select>
                        <div class="form-text">
                            <strong>Fresh Verification:</strong> Numbers with live coverage will be verified with fresh API calls.
                            Numbers without live coverage will be skipped to save costs and only checked against local database.
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

    <script>
        // API Configuration from Laravel
        const API_CONFIG = {
            baseUrl: window.location.origin,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        };
    </script>
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

        // Apple-style batch results display
        function showBatchResults(data) {
            // Remove existing results card if any
            const existingCard = document.getElementById('batch-results-card');
            if (existingCard) {
                existingCard.remove();
            }

            const stats = data.statistics || {};
            const liveCoverageCount = data.live_coverage_count || 0;
            const noCoverageCount = data.no_coverage_count || 0;
            const errorCount = data.error_count || 0;
            const cacheHits = stats.cache_hits || 0;
            const dbHits = stats.database_hits || 0;
            const apiCalls = stats.api_calls || 0;
            const totalCached = cacheHits + dbHits;

            // Calculate percentages
            const liveCoveragePercent = data.processed > 0 ? Math.round((liveCoverageCount / data.processed) * 100) : 0;
            const noCoveragePercent = data.processed > 0 ? Math.round((noCoverageCount / data.processed) * 100) : 0;
            const cachePercent = data.processed > 0 ? Math.round((totalCached / data.processed) * 100) : 0;

            const resultsHtml = `
                <div id="batch-results-card" class="card mb-4" style="border: 1px solid #ddd;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">Batch Verification Results</h5>
                            <button type="button" class="btn-close" onclick="document.getElementById('batch-results-card').remove()" style="background: none; border: none; font-size: 18px;">&times;</button>
                        </div>

                        <!-- Main Statistics Row -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <div class="text-center p-3" style="border: 1px solid #eee; border-radius: 8px;">
                                    <div style="font-size: 24px; font-weight: bold; margin-bottom: 4px; color: #333;">${data.processed}</div>
                                    <div style="font-size: 14px; color: #666;">Total Processed</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3" style="border: 1px solid #eee; border-radius: 8px;">
                                    <div style="font-size: 24px; font-weight: bold; margin-bottom: 4px; color: #28a745;">${liveCoverageCount}</div>
                                    <div style="font-size: 14px; color: #666;">Live Coverage</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3" style="border: 1px solid #eee; border-radius: 8px;">
                                    <div style="font-size: 24px; font-weight: bold; margin-bottom: 4px; color: #ffc107;">${noCoverageCount}</div>
                                    <div style="font-size: 14px; color: #666;">No Coverage</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3" style="border: 1px solid #eee; border-radius: 8px;">
                                    <div style="font-size: 24px; font-weight: bold; margin-bottom: 4px; color: ${errorCount > 0 ? '#dc3545' : '#6c757d'};">${errorCount}</div>
                                    <div style="font-size: 14px; color: #666;">Errors</div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Source Statistics -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="text-center p-3" style="background: #f8f9fa; border-radius: 8px;">
                                    <div style="font-size: 20px; font-weight: bold; margin-bottom: 4px; color: #007bff;">${totalCached}</div>
                                    <div style="font-size: 14px; color: #666;">From Cache/DB</div>
                                    <div style="font-size: 12px; color: #999;">${cachePercent}% cache hit</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center p-3" style="background: #f8f9fa; border-radius: 8px;">
                                    <div style="font-size: 20px; font-weight: bold; margin-bottom: 4px; color: #17a2b8;">${cacheHits}</div>
                                    <div style="font-size: 14px; color: #666;">Redis Cache</div>
                                    <div style="font-size: 12px; color: #999;">Fast retrieval</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center p-3" style="background: #f8f9fa; border-radius: 8px;">
                                    <div style="font-size: 20px; font-weight: bold; margin-bottom: 4px; color: #6f42c1;">${dbHits}</div>
                                    <div style="font-size: 14px; color: #666;">Database</div>
                                    <div style="font-size: 12px; color: #999;">Historical data</div>
                                </div>
                            </div>
                        </div>

                        <!-- API Calls and Performance -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div style="font-weight: 500; margin-bottom: 8px; color: #333;">API Performance</div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span style="color: #666;">New API Calls:</span>
                                        <span style="font-weight: 500; color: #333;">${apiCalls}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span style="color: #666;">Cache Hit Rate:</span>
                                        <span style="font-weight: 500; color: #333;">${cachePercent}%</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span style="color: #666;">Coverage Rate:</span>
                                        <span style="font-weight: 500; color: #333;">${liveCoveragePercent}%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div>
                                    <div style="font-weight: 500; margin-bottom: 8px; color: #333;">Cost Optimization</div>
                                    <div style="font-size: 14px; color: #666;">
                                        <div class="mb-1">${noCoverageCount} API calls saved (no coverage)</div>
                                        <div class="mb-1">${totalCached} cached results reused</div>
                                        <div>${apiCalls} fresh verifications made</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        ${data.cache_message ? `
                        <div class="mt-3 pt-3" style="border-top: 1px solid #eee;">
                            <div style="font-size: 13px; color: #666; font-style: italic;">
                                ${data.cache_message}
                            </div>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;

            // Insert the results card after the alert container
            const alertContainer = document.getElementById('alert-container');
            alertContainer.insertAdjacentHTML('afterend', resultsHtml);
        }

        function hideAlert() {
            const alertContainer = document.getElementById('alert-container');
            alertContainer.style.display = 'none';
        }

        // Show verification result card
        function showVerificationCard(verificationData) {
            // Remove existing card if any
            const existingCard = document.getElementById('verification-result-card');
            if (existingCard) {
                existingCard.innerHTML = '';
                existingCard.style.display = 'none';
            }

            // Prepare data with null handling
            const data = {
                number: verificationData.phone_number || verificationData.number || 'N/A',
                local_format: verificationData.local_format || null,
                valid: verificationData.valid || null,
                present: verificationData.present || null,
                fraud_score: verificationData.fraud_score || null,
                recent_abuse: verificationData.recent_abuse || null,
                voip: verificationData.voip || null,
                prepaid: verificationData.prepaid || null,
                risky: verificationData.risky || null,
                network: verificationData.network || null,
                type: verificationData.type || null,
                prefix: verificationData.prefix || null,
                leaked_online: verificationData.leaked_online || null,
                spammer: verificationData.spammer || null,
                country: verificationData.country || null,
                city: verificationData.city || null,
                region: verificationData.region || null,
                zip_code: verificationData.zip_code || null,
                timezone: verificationData.timezone || null,
                dialing_code: verificationData.dialing_code || null,
                cic: verificationData.cic || null,
                error: verificationData.error || null,
                imsi: verificationData.imsi || null,
                mcc: verificationData.mcc || null,
                mnc: verificationData.mnc || null,
                ocn: verificationData.ocn || null,
                ported: verificationData.ported || null,
                status: verificationData.status || null,
                status_message: verificationData.status_message || null,
                trxid: verificationData.trxid || null,
                created_at: verificationData.created_at || null
            };

            // Create card HTML with simplified black and primary colors only
            const cardHtml = `
                <div class="card border-0 shadow-sm">
                    <div class="card-header text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-phone-alt me-2"></i>
                                Phone Verification Results
                            </h5>
                            <button type="button" class="btn btn-sm btn-outline-light" onclick="hideVerificationCard()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Basic Information -->
                            <div class="col-md-6 mb-4">
                                <h6 class="border-bottom pb-2 mb-3 text-dark">
                                    <i class="fas fa-info-circle me-2"></i>Basic Information
                                </h6>
                                <div class="info-row">
                                    <strong>Phone Number:</strong>
                                    <span class="badge bg-dark text-white">${data.number}</span>
                                </div>
                                <div class="info-row">
                                    <strong>Local Format:</strong>
                                    <span>${data.local_format || '<span class="text-muted">NULL</span>'}</span>
                                </div>
                                <div class="info-row">
                                    <strong>Valid:</strong>
                                    <span>${data.valid || '<span class="text-muted">NULL</span>'}</span>
                                </div>
                                <div class="info-row">
                                    <strong>Present:</strong>
                                    <span class="badge ${data.present === 'yes' ? 'bg-primary' : 'bg-dark'} text-white">
                                        ${data.present || 'NULL'}
                                    </span>
                                </div>
                                <div class="info-row">
                                    <strong>Status:</strong>
                                    <span class="badge ${data.status === 0 ? 'bg-primary' : 'bg-dark'} text-white">
                                        ${data.status_message || 'NULL'}
                                    </span>
                                </div>
                            </div>

                            <!-- Security Information -->
                            <div class="col-md-6 mb-4">
                                <h6 class="border-bottom pb-2 mb-3 text-dark">
                                    <i class="fas fa-shield-alt me-2"></i>Security Information
                                </h6>
                                <div class="info-row">
                                    <strong>Fraud Score:</strong>
                                    <span>${data.fraud_score || '<span class="text-muted">NULL</span>'}</span>
                                </div>
                                <div class="info-row">
                                    <strong>Recent Abuse:</strong>
                                    <span>${data.recent_abuse || '<span class="text-muted">NULL</span>'}</span>
                                </div>
                                <div class="info-row">
                                    <strong>VoIP:</strong>
                                    <span>${data.voip || '<span class="text-muted">NULL</span>'}</span>
                                </div>
                                <div class="info-row">
                                    <strong>Prepaid:</strong>
                                    <span>${data.prepaid || '<span class="text-muted">NULL</span>'}</span>
                                </div>
                                <div class="info-row">
                                    <strong>Risky:</strong>
                                    <span>${data.risky || '<span class="text-muted">NULL</span>'}</span>
                                </div>
                                <div class="info-row">
                                    <strong>Leaked Online:</strong>
                                    <span>${data.leaked_online || '<span class="text-muted">NULL</span>'}</span>
                                </div>
                                <div class="info-row">
                                    <strong>Spammer:</strong>
                                    <span>${data.spammer || '<span class="text-muted">NULL</span>'}</span>
                                </div>
                            </div>

                            <!-- Network Information -->
                            <div class="col-md-6 mb-4">
                                <h6 class="border-bottom pb-2 mb-3 text-dark">
                                    <i class="fas fa-network-wired me-2"></i>Network Information
                                </h6>
                                <div class="info-row">
                                    <strong>Network:</strong>
                                    <span class="badge bg-primary text-white">${data.network || 'NULL'}</span>
                                </div>
                                <div class="info-row">
                                    <strong>Type:</strong>
                                    <span class="badge bg-dark text-white">${data.type || 'NULL'}</span>
                                </div>
                                <div class="info-row">
                                    <strong>Prefix:</strong>
                                    <span>${data.prefix || '<span class="text-muted">NULL</span>'}</span>
                                </div>
                                <div class="info-row">
                                    <strong>MCC/MNC:</strong>
                                    <span>${data.mcc || 'NULL'}/${data.mnc || 'NULL'}</span>
                                </div>
                                <div class="info-row">
                                    <strong>Ported:</strong>
                                    <span class="badge ${data.ported ? 'bg-dark' : 'bg-primary'} text-white">
                                        ${data.ported ? 'Yes' : data.ported === false ? 'No' : 'NULL'}
                                    </span>
                                </div>
                                <div class="info-row">
                                    <strong>CIC:</strong>
                                    <span>${data.cic || '<span class="text-muted">NULL</span>'}</span>
                                </div>
                                <div class="info-row">
                                    <strong>IMSI:</strong>
                                    <span>${data.imsi || '<span class="text-muted">NULL</span>'}</span>
                                </div>
                                <div class="info-row">
                                    <strong>OCN:</strong>
                                    <span>${data.ocn || '<span class="text-muted">NULL</span>'}</span>
                                </div>
                            </div>

                            <!-- Location Information -->
                            <div class="col-md-6 mb-4">
                                <h6 class="border-bottom pb-2 mb-3 text-dark">
                                    <i class="fas fa-map-marker-alt me-2"></i>Location Information
                                </h6>
                                <div class="info-row">
                                    <strong>Country:</strong>
                                    <span class="badge bg-primary text-white">${data.country || 'NULL'}</span>
                                </div>
                                <div class="info-row">
                                    <strong>City:</strong>
                                    <span>${data.city || '<span class="text-muted">NULL</span>'}</span>
                                </div>
                                <div class="info-row">
                                    <strong>Region:</strong>
                                    <span>${data.region || '<span class="text-muted">NULL</span>'}</span>
                                </div>
                                <div class="info-row">
                                    <strong>ZIP Code:</strong>
                                    <span>${data.zip_code || '<span class="text-muted">NULL</span>'}</span>
                                </div>
                                <div class="info-row">
                                    <strong>Timezone:</strong>
                                    <span>${data.timezone || '<span class="text-muted">NULL</span>'}</span>
                                </div>
                                <div class="info-row">
                                    <strong>Dialing Code:</strong>
                                    <span>${data.dialing_code || '<span class="text-muted">NULL</span>'}</span>
                                </div>
                            </div>

                            <!-- Technical Details -->
                            <div class="col-12">
                                <h6 class="border-bottom pb-2 mb-3 text-dark">
                                    <i class="fas fa-cogs me-2"></i>Technical Details
                                </h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="info-row">
                                            <strong>Error Code:</strong>
                                            <span>${data.error || '<span class="text-muted">NULL</span>'}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-row">
                                            <strong>Transaction ID:</strong>
                                            <span class="text-monospace">${data.trxid || '<span class="text-muted">NULL</span>'}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-row">
                                            <strong>Verified At:</strong>
                                            <span>${data.created_at ? new Date(data.created_at).toLocaleString() : '<span class="text-muted">NULL</span>'}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <style>
                .info-row {
                    margin-bottom: 8px;
                    padding: 4px 0;
                }
                .info-row strong {
                    display: inline-block;
                    min-width: 120px;
                    color: #495057;
                }
                .text-muted {
                    font-style: italic;
                }
                </style>
            `;

            existingCard.innerHTML = cardHtml;
            existingCard.style.display = 'block';

            // Scroll to the card
            existingCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // Hide verification result card
        function hideVerificationCard() {
            const cardContainer = document.getElementById('verification-result-card');
            if (cardContainer) {
                cardContainer.style.display = 'none';
            }
        }

        // Update table row function
        function updateTableRow(verificationData) {
            console.log('updateTableRow called with:', verificationData);
            const table = $('#network_verification_table').DataTable();
            const phoneNumber = verificationData.phone_number || verificationData.number;

            // Skip adding rows for numbers without live coverage
            if (verificationData.skip_reason === 'no_live_coverage') {
                console.log('Skipping table update for number without live coverage:', phoneNumber);
                return;
            }

            const now = new Date();
            const formattedDate = now.getFullYear() + '-' +
                String(now.getMonth() + 1).padStart(2, '0') + '-' +
                String(now.getDate()).padStart(2, '0');

            const statusClass = (verificationData.status === 0) ? 'success' : 'danger';
            const statusText = verificationData.status === 0 ? 'Success' : 'Failed';
            const statusMessage = verificationData.status_message || 'Unknown';
            const portedClass = verificationData.ported ? 'success' : 'secondary';
            const portedText = verificationData.ported ? 'Yes' : 'No';

            // Determine live coverage status - only show if has live coverage
            let liveCoverageClass = 'success';
            let liveCoverageText = 'Yes';

            if (verificationData.prefix_info && verificationData.prefix_info.live_coverage !== undefined) {
                liveCoverageClass = verificationData.prefix_info.live_coverage ? 'success' : 'danger';
                liveCoverageText = verificationData.prefix_info.live_coverage ? 'Yes' : 'No';
            }

            const rowData = [
                phoneNumber,
                verificationData.network_prefix?.country_name || verificationData.country_name || 'Cambodia',
                verificationData.network || verificationData.network_name || 'Unknown Network',
                (verificationData.mcc || '') + '/' + (verificationData.mnc || ''),
                `<span class="badge badge-pill badge-outline-${liveCoverageClass} p-2 m-1">${liveCoverageText}</span>`,
                verificationData.type ? verificationData.type.charAt(0).toUpperCase() + verificationData.type.slice(1) : 'Unknown',
                `<span class="badge badge-pill badge-outline-${statusClass} p-2 m-1">${statusText}</span>`,
                statusMessage,
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
                        presentClass = 'secondary';
                    }

                    return `<span class="badge badge-pill badge-outline-${presentClass} p-2 m-1">${presentText}</span>`;
                })(),
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
                $(newNode).css('cursor', 'pointer');
                $(newNode).attr('title', 'Click to view detailed verification results');

                // Add click handler to show card
                $(newNode).on('click', function() {
                    showVerificationCard(verificationData);
                });

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

        // Load verifications from server-side data
        function loadVerifications() {
            console.log('Loading verifications from server-side data...');

            // Use the verifications data passed from the controller
            const verifications = @json($verifications ?? []);
            console.log('Server-side verifications:', verifications);

            if (verifications && verifications.length > 0) {
                tableVerification(verifications);
                console.log(`Loaded ${verifications.length} verifications from server data`);
            } else {
                console.log('No verifications found in server data');
                showAlert('info', 'No Data', 'No verification records found.');
            }
        }

        // Populate the verification table with server data
        function tableVerification(verifications) {
            // Wait for DataTable to be initialized by datatables.script.js
            if (!$.fn.DataTable.isDataTable('#network_verification_table')) {
                // If DataTable is not ready yet, wait a bit and try again
                setTimeout(() => tableVerification(verifications), 100);
                return;
            }

            const table = $('#network_verification_table').DataTable();

            // Clear existing data
            table.clear();

            if (verifications && verifications.length > 0) {
                verifications.forEach(function(verification) {
                    const liveCoverage = verification.network_prefix?.live_coverage ?? (verification.status === 0);
                    const liveCoverageClass = liveCoverage ? 'success' : 'danger';
                    const liveCoverageText = liveCoverage ? 'Yes' : 'No';

                    const statusClass = (verification.status === 0) ? 'success' : 'danger';
                    const statusText = verification.status === 0 ? 'Success' : 'Failed';
                    const statusMessage = verification.status_message || 'Unknown';

                    const portedClass = verification.ported ? 'success' : 'secondary';
                    const portedText = verification.ported ? 'Yes' : 'No';

                    // Handle present field
                    let present = (verification.present || 'na').toLowerCase();
                    if (!['yes', 'no', 'na'].includes(present)) {
                        present = 'na';
                    }
                    const presentText = present.charAt(0).toUpperCase() + present.slice(1);
                    let presentClass = 'secondary';
                    if (present === 'yes') {
                        presentClass = 'success';
                    }

                    const rowData = [
                        verification.number,
                        verification.network_prefix?.country_name || verification.country_name || 'Unknown',
                        verification.network || verification.network_name || 'Unknown',
                        (verification.mcc || '') + '/' + (verification.mnc || ''),
                        `<span class="badge badge-pill badge-outline-${liveCoverageClass} p-2 m-1">${liveCoverageText}</span>`,
                        verification.type ? verification.type.charAt(0).toUpperCase() + verification.type.slice(1) : 'Unknown',
                        `<span class="badge badge-pill badge-outline-${statusClass} p-2 m-1">${statusText}</span>`,
                        statusMessage,
                        `<span class="badge badge-pill badge-outline-${portedClass} p-2 m-1">${portedText}</span>`,
                        `<span class="badge badge-pill badge-outline-${presentClass} p-2 m-1">${presentText}</span>`,
                        new Date(verification.created_at).toLocaleDateString()
                    ];

                    // Add row with data-phone attribute
                    const newRow = table.row.add(rowData).draw(false);
                    const newNode = newRow.node();
                    $(newNode).attr('data-phone', verification.number);
                    $(newNode).css('cursor', 'pointer');
                    $(newNode).attr('title', 'Click to view detailed verification results');

                    // Add click handler to show card
                    $(newNode).on('click', function() {
                        showVerificationCard(verification);
                    });
                });

                console.log(`Loaded ${verifications.length} verifications from API`);
            } else {
                console.log('No verifications returned from API');
            }

            table.draw();
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Load verifications from server-side data
            loadVerifications();

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
                    const response = await fetch('/verification/check', {
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

                if (result.success) {
                    const countryName = result.country_name || 'Unknown';
                    const networkName = result.network_name || 'Unknown';

                    // Enhanced display for international scenarios
                    if (result.ambiguous) {
                        // Multiple countries possible - show helpful information
                        detectedInfo.textContent = `${countryName} - ${networkName}`;
                        coverageStatus.textContent = `Continue typing (${result.countries?.join(', ') || 'Multiple options'})`;
                        validationAlert.className = 'alert alert-warning mb-0';

                        phoneInput.classList.remove('is-invalid', 'is-valid');
                        phoneError.textContent = '';
                        verifyBtn.disabled = true;
                        verifyBtn.title = 'Continue typing to identify the country';
                    } else {
                        detectedInfo.textContent = `${countryName} - ${networkName} (${result.prefix})`;

                        // Handle partial matches (country code inputs) - show info without errors
                        if (result.partial_match) {
                            validationAlert.className = 'alert alert-info mb-0';
                            coverageStatus.textContent = result.live_coverage ? 'Available' : 'Not Available';

                            // Don't show input as invalid during typing - just neutral
                            phoneInput.classList.remove('is-invalid', 'is-valid');
                            phoneError.textContent = ''; // No error message during typing

                            // Disable verify button for incomplete numbers (but don't show error)
                            verifyBtn.disabled = true;
                            verifyBtn.title = 'Complete the phone number to enable verification';
                        } else if (result.live_coverage) {
                            validationAlert.className = 'alert alert-success mb-0';
                            coverageStatus.textContent = 'Available - API call will be made';
                            phoneInput.classList.remove('is-invalid');
                            phoneInput.classList.add('is-valid');
                            phoneError.textContent = '';

                            // Enable verify button for complete valid numbers
                            verifyBtn.disabled = false;
                            verifyBtn.title = '';
                        } else {
                            validationAlert.className = 'alert alert-warning mb-0';
                            coverageStatus.textContent = 'Not Available - Verification disabled';
                            phoneInput.classList.remove('is-invalid');
                            phoneInput.classList.add('is-valid');
                            phoneError.textContent = '';

                            // Disable verify button for no coverage numbers
                            verifyBtn.disabled = true;
                            verifyBtn.title = 'This operator does not support live coverage verification';
                        }
                    }

                    validationInfo.style.display = 'block';
                } else {
                    validationAlert.className = 'alert alert-secondary mb-0';
                    detectedInfo.textContent = 'Network prefix not found in database';
                    coverageStatus.textContent = 'Unknown';
                    validationInfo.style.display = 'block';
                    phoneInput.classList.remove('is-valid');
                    phoneInput.classList.add('is-invalid');
                    phoneError.textContent = result.error || 'Network prefix not found';

                    // Disable verify button for invalid numbers
                    verifyBtn.disabled = true;
                    verifyBtn.title = 'Enter a valid phone number to enable verification';
                }
            }

            // Add real-time validation to phone input
            phoneInput.addEventListener('input', function(e) {
                phoneError.textContent = '';

                // Disable verify button immediately when user starts typing
                verifyBtn.disabled = true;
                verifyBtn.title = 'Validating phone number...';

                clearTimeout(phoneInput.validationTimeout);
                phoneInput.validationTimeout = setTimeout(() => {
                    validateNetworkPrefix(e.target.value);
                }, 500);
            });

            // Initial state - disable verify button when modal opens
            verifyBtn.disabled = true;
            verifyBtn.title = 'Enter a phone number to enable verification';

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

                    verifyBtn.disabled = true; // Keep disabled for invalid numbers
                    spinner.classList.add('d-none');
                    verifyBtn.innerHTML = 'Verify';
                    return;
                }

                // Check if number is incomplete (partial match)
                if (validationResult.partial_match) {
                    phoneInput.classList.add('is-invalid');
                    phoneError.textContent = `Phone number is incomplete. Please enter ${validationResult.min_length}-${validationResult.max_length} digits.`;
                    showAlert('warning', 'Incomplete Number', `Please enter the complete phone number (${validationResult.min_length}-${validationResult.max_length} digits) before verification.`);

                    verifyBtn.disabled = true; // Keep disabled for incomplete numbers
                    spinner.classList.add('d-none');
                    verifyBtn.innerHTML = 'Verify';
                    return;
                }


                verifyBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Verifying...';

                // Make API call to web verification route
                fetch('/verification/verify', {
                        method: 'POST',
                        headers: API_CONFIG.headers,
                        body: JSON.stringify({
                            phone_number: phoneNumber,
                            data_freshness: dataFreshness
                        })
                    })
                    .then(response => {
                        console.log('Raw response status:', response.status);
                        console.log('Raw response ok:', response.ok);
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Parsed response data:', data);
                        if (data.success) {
                            $(modal).modal('hide');

                            // Show verification result card for all successful verifications
                            showVerificationCard(data.data);

                            // Handle different sources from our API
                            if (data.source === 'cache') {
                                showAlert('info', 'Cache Hit', 'Phone number verification retrieved from cache.');
                                highlightRow(data.data.number);
                            } else if (data.source === 'database') {
                                showAlert('info', 'Database Hit', 'Phone number verification retrieved from database.');
                                highlightRow(data.data.number);
                            } else if (data.source === 'tmt_api') {
                                showAlert('success', 'TMT API Call', 'Phone number verified with fresh TMT API call.');
                                updateTableRow(data.data);
                            } else {
                                // Fallback for any other cases
                                showAlert('success', 'Verification Complete', 'Phone number verified successfully!');
                                updateTableRow(data.data);
                            }

                            // For cache/database hits, we could optionally reload the page to get fresh server data
                            // if (data.source === 'cache' || data.source === 'database') {
                            //     window.location.reload();
                            // }
                        } else {
                            phoneInput.classList.add('is-invalid');
                            phoneError.textContent = data.message || 'Verification failed';
                            showAlert('danger', 'Error', data.message || 'Phone number verification failed.');
                        }
                    })
                    .catch(error => {
                        console.error('Detailed error information:', error);
                        console.error('Error message:', error.message);
                        console.error('Error stack:', error.stack);
                        phoneInput.classList.add('is-invalid');
                        phoneError.textContent = 'Network error. Please try again.';
                        showAlert('danger', 'Error', 'Network error occurred. Please try again. Check console for details.');
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
                verifyBtn.disabled = true; // Start with disabled button
                verifyBtn.title = 'Enter a phone number to enable verification';
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

                fetch('{{ route("verification.batch") }}', {
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

                            // Create Apple-style results display
                            showBatchResults(data);

                            // Simple success message
                            showAlert('success', 'Batch Verification Complete', `Successfully processed ${data.processed} phone numbers.`);

                            if (data.data && data.data.length > 0) {
                                data.data.forEach(verification => {
                                    if (verification.source !== 'cache' && verification.skip_reason !== 'no_live_coverage') {
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

<!-- Filter Modal - YouTube Style -->
<div class="modal fade" id="filterModal" tabindex="-1" role="dialog" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 4px 24px rgba(0,0,0,0.15);">
            <div class="modal-header border-0 px-4 pt-4 pb-2">
                <h5 class="modal-title" id="filterModalLabel" style="font-weight: 500; color: #333;">
                    Filter Results
                </h5>
            </div>
            <div class="modal-body px-4 py-2">
                <div id="modal-filters">
                    <!-- Coverage Category -->
                    <div class="filter-category mb-4">
                        <h6 class="filter-category-title" style="font-size: 14px; font-weight: 600; color: #666; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                            Coverage
                        </h6>
                        <div class="filter-options">
                            <select id="modal-coverage-filter" class="form-select" style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 8px 12px; font-size: 14px;">
                                <option value="">All Coverage Types</option>
                                <option value="Yes">Live Coverage</option>
                                <option value="No">No Coverage</option>
                            </select>
                        </div>
                    </div>

                    <!-- Connection Category -->
                    <div class="filter-category mb-4">
                        <h6 class="filter-category-title" style="font-size: 14px; font-weight: 600; color: #666; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                            Connection
                        </h6>
                        <div class="row">
                            <div class="col-6">
                                <select id="modal-type-filter" class="form-select" style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 8px 12px; font-size: 14px;">
                                    <option value="">All Types</option>
                                    <option value="Mobile">Mobile</option>
                                    <option value="Fixed">Fixed</option>
                                    <option value="Unknown">Unknown</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <select id="modal-ported-filter" class="form-select" style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 8px 12px; font-size: 14px;">
                                    <option value="">All Porting</option>
                                    <option value="Yes">Ported</option>
                                    <option value="No">Not Ported</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Status Category -->
                    <div class="filter-category mb-4">
                        <h6 class="filter-category-title" style="font-size: 14px; font-weight: 600; color: #666; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                            Status
                        </h6>
                        <div class="row">
                            <div class="col-6">
                                <select id="modal-status-filter" class="form-select" style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 8px 12px; font-size: 14px;">
                                    <option value="">All Status</option>
                                    <option value="Success">Success</option>
                                    <option value="Failed">Failed</option>
                                    <option value="No Live Coverage">No Coverage</option>
                                    <option value="Error">Error</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <select id="modal-present-filter" class="form-select" style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 8px 12px; font-size: 14px;">
                                    <option value="">All Presence</option>
                                    <option value="Yes">Present</option>
                                    <option value="No">Not Present</option>
                                    <option value="Na">N/A</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Filters Preview -->
                <div class="mt-3 pt-3" style="border-top: 1px solid #f0f0f0;">
                    <div class="d-flex align-items-center">
                        <small class="text-muted me-2" style="font-size: 12px;">Active filters:</small>
                        <small id="activeFiltersText" class="text-dark" style="font-size: 12px;">None</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="clearAllFilters">
                    Clear All
                </button>
                <button type="button" class="btn btn-primary btn-sm" id="applyFilters">
                    Apply Filters
                </button>
            </div>
        </div>
    </div>
</div>