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
            <h1>Phone Number Verification</h1>
            <ul class="mb-0">
                <li><a href="">Dashboard</a></li>
                <li>Verification</li>
            </ul>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-secondary" data-toggle="modal" data-target="#filterModal" id="filterBtn">
                <i class="nav-icon me-2 i-Filter-2"></i> <span id="filterCount" class="badge badge-light d-none">0</span>
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

    <!-- Phone Verification Input and Results Row -->
    <div class="row mb-4">
        <!-- Left Side - Input Form -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <form id="verifyForm">
                        @csrf
                        <div class="mb-3">
                            <label for="phone_number_main" class="col-form-label">Enter Phone Number</label>
                            <div class="input-group">
                                <input type="tel" class="form-control" id="phone_number_main" name="phone_number"
                                    placeholder="e.g., 85592313242" required>
                                <button type="button" class="btn btn-primary" id="verifyBtnMain">
                                    <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                                    Verify
                                </button>
                            </div>
                             <div class="text-danger small" id="phone-error-main" style="display: none;"></div>
                        </div>

                        <!-- Phone Validation Info -->
                        <div class="mb-3" id="phone-validation-info-main" style="display: none;">
                            <div class="alert mb-0" id="validation-alert-main">
                                <div class="row">
                                    <div class="col-md-8">
                                        <strong>Detected:</strong> <span id="detected-info-main"></span>
                                    </div>
                                    <div class="col-md-4 text-end" hidden>
                                        <strong>Coverage:</strong> <span id="coverage-status-main"></span>
                                    </div>
                                </div>
                                <div class="row mt-2" id="cost-info-main" style="display: none;">
                                    <div class="col-12">
                                        <strong>Estimated Cost:</strong> <span id="cost-amount-main"
                                            class="badge bg-primary"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Query Type Selection -->
                        <div class="mb-3">
                            <label class="col-form-label">Query Type</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="query_type_main" id="basic_query_main"
                                    value="basic" checked>
                                <label class="form-check-label" for="basic_query_main">
                                    <strong>BASIC Query</strong> <small class="text-muted">(FREE)</small>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="query_type_main" id="advanced_query_main"
                                    value="advanced">
                                <label class="form-check-label" for="advanced_query_main">
                                    <strong>ADVANCED Query</strong> <small class="text-muted">(PAID)</small>
                                </label>
                            </div>
                        </div>

                        <!-- Advanced Options -->
                        <div class="mb-3" id="advanced-options-main" style="display: none;">
                            <label for="data_freshness_main" class="col-form-label">Data Freshness</label>
                            <select class="form-control" id="data_freshness_main" name="data_freshness">
                                <option value="">Cached (recommended)</option>
                                <option value="30">Refresh after 30 days</option>
                                <option value="60">Refresh after 60 days</option>
                                <option value="90">Refresh after 90 days</option>
                                <option value="all">Always fresh</option>
                            </select>
                         </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Side - Results Display -->
        <div class="col-md-6">
            <div id="verification-result-main" style="display: none;">
                <!-- Results will be displayed here -->
            </div>
        </div>
    </div>

    <div class="row" id="verification-table-container" style="display: none;">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0"><i class="fas fa-table"></i> Network Prefix Verification Table</h4>
                        <div class="d-flex gap-2">
                            <a href="{{ route('verification.export') }}" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-download"></i> Export to Excel
                            </a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="network_verification_table" class="display table table-striped table-bordered"
                            style="width:100%">
                            <thead>
                                <tr>
                                    <th>Number</th>
                                    <th>Provider</th>
                                    <th>Cost</th>
                                    <th>Country</th>
                                    <th>Network/Carrier</th>
                                    <th>Status</th>
                                    <th>Valid</th>
                                    <th>Present</th>
                                    <th>Ported</th>
                                    <th>Fraud Score</th>
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
                            <option value="">Cached (recommended)</option>
                            <option value="30">Refresh after 30 days</option>
                            <option value="60">Refresh after 60 days</option>
                            <option value="90">Refresh after 90 days</option>
                            <option value="all">Always fresh</option>
                        </select>
                        <div class="form-text">
                            <strong>Fresh Verification:</strong> Numbers with live coverage will be verified with fresh API
                            calls.
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
        console.log('API_CONFIG:', API_CONFIG); // Debug output
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

        // Show advanced verification results in main panel
        function showVerificationCardMain(verificationData) {
            const resultContainer = document.getElementById('verification-result-main');

            // Determine the provider and map fields accordingly
            const provider = verificationData.provider;
            let valid, present, country, fraudScore;

            if (provider === 'TMT') {
                // TMT mapping - country comes from coverage data, not API response
                valid = verificationData.tmt_status !== undefined ? (verificationData.tmt_status === 0 ? true : false) : null;
                present = verificationData.tmt_present || null;
                // Get country from coverage data passed back from service
                country = verificationData.coverage_country_name || verificationData.coverage_country || 'N/A';
                fraudScore = 'N/A'; // TMT doesn't provide fraud scores
            } else if (provider === 'IPQS') {
                // IPQS mapping
                valid = verificationData.ipqs_valid || null;
                present = verificationData.ipqs_active !== undefined ? (verificationData.ipqs_active ? 'yes' : 'no') : null;
                country = verificationData.ipqs_country || null;
                fraudScore = verificationData.ipqs_fraud_score || null;
            } else {
                // Fallback for unknown providers
                valid = verificationData.ipqs_valid || (verificationData.tmt_status !== undefined ? (verificationData.tmt_status === 0) : null);
                present = verificationData.tmt_present || (verificationData.ipqs_active !== undefined ? (verificationData.ipqs_active ? 'yes' : 'no') : null);
                country = verificationData.ipqs_country || verificationData.tmt_country || null;
                fraudScore = verificationData.ipqs_fraud_score || null;
            }

            const data = {
                number: verificationData.phone_number || verificationData.number || 'N/A',
                provider: provider || 'Unknown',
                valid: valid,
                present: present,
                fraud_score: fraudScore,
                network: verificationData.tmt_network || verificationData.ipqs_carrier || null,
                country: country,
                status: verificationData.tmt_status !== undefined ? (verificationData.tmt_status === 0 ? 'Success' : 'Failed') : 'Unknown',
                ported: verificationData.tmt_ported !== undefined ? (verificationData.tmt_ported ? 'Yes' : 'No') : 'N/A',
                mcc: verificationData.tmt_mcc || 'N/A',
                mnc: verificationData.tmt_mnc || 'N/A',
                prefix: verificationData.tmt_prefix || 'N/A',
                cost: verificationData.cost ? '$' + parseFloat(verificationData.cost).toFixed(6) : 'N/A',
                created_at: verificationData.created_at || null
            };

            const cardHtml = `
                    <div class="card border-0 shadow-sm">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-phone-alt me-2"></i>
                                Advanced Result (${data.provider})
                            </h5>
                        </div>
                        <div class="card-body">

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <h6 class="mb-2 text-dark">Basic Information</h6>

                                    <div class="d-flex justify-content-between mb-1 border-bottom">
                                        <strong>Phone Number:</strong>
                                        <span>${data.number}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1 border-bottom">
                                        <strong>Provider:</strong>
                                        <span>${data.provider}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1 border-bottom">
                                        <strong>Status:</strong>
                                        <span>${data.status}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1 border-bottom">
                                        <strong>Valid:</strong>
                                        <span>${data.valid !== null ? (data.valid ? 'Yes' : 'No') : 'N/A'}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1 border-bottom">
                                        <strong>Present:</strong>
                                        <span>${data.present ? data.present.charAt(0).toUpperCase() + data.present.slice(1) : 'N/A'}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <strong>Cost:</strong>
                                        <span>${data.cost}</span>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <h6 class="mb-2 text-dark">Network Information</h6>

                                    <div class="d-flex justify-content-between mb-1 border-bottom">
                                        <strong>Network:</strong>
                                        <span>${data.network || 'N/A'}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1 border-bottom">
                                        <strong>Country:</strong>
                                        <span>${data.country || 'N/A'}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1 border-bottom">
                                        <strong>Prefix:</strong>
                                        <span>${data.prefix}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1 border-bottom">
                                        <strong>MCC/MNC:</strong>
                                        <span>${data.mcc}/${data.mnc}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1 border-bottom">
                                        <strong>Ported:</strong>
                                        <span>${data.ported}</span>
                                    </div>
                                    ${data.fraud_score !== 'N/A' ? `
                                    <div class="d-flex justify-content-between mb-1">
                                        <strong>Fraud Score:</strong>
                                        <span>${data.fraud_score}</span>
                                    </div>
                                    ` : ''}
                                </div>
                            </div>

                            <div class="border-top pt-2">
                                <div class="d-flex justify-content-between">
                                    <strong>Verified At:</strong>
                                    <span class="text-muted">${data.created_at ? new Date(data.created_at).toLocaleString() : 'N/A'}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            resultContainer.innerHTML = cardHtml;
            resultContainer.style.display = 'block';
        }

        // Show basic query results in main panel
        function showBasicQueryResultsMain(data) {
            const resultContainer = document.getElementById('verification-result-main');

            const cardHtml = `
                    <div class="card border-0 shadow-sm">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-search me-2"></i>
                                Basic Result
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                                <strong>Phone Number:</strong>
                                <span>${data.phone_number}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                                <strong>Supported:</strong>
                                <span>${data.supported ? 'Yes' : 'No'}</span>
                            </div>
                            ${data.coverage_info && data.coverage_info.country ? `
                            <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                                <strong>Country:</strong>
                                <span>${data.coverage_info.country}</span>
                            </div>
                            ` : ''}
                            ${data.coverage_info && data.coverage_info.country_code ? `
                            <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                                <strong>Dialing Code:</strong>
                                <span>+${data.coverage_info.country_code}</span>
                            </div>
                            ` : ''}
                            ${data.coverage_info && (data.coverage_info.network_name || data.coverage_info.carrier_name) ? `
                            <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                                <strong>Network/Carrier:</strong>
                                <span>${data.coverage_info.network_name || data.coverage_info.carrier_name}</span>
                            </div>
                            ` : ''}
                            ${data.coverage_info && (data.coverage_info.live_coverage !== undefined || data.coverage_info.support_provider !== undefined) ? `
                            <div class="d-flex justify-content-between mb-2 pb-1">
                                <strong>Status:</strong>
                                <span>${(data.coverage_info.live_coverage || data.coverage_info.support_provider) ? 'Active' : 'Inactive'}</span>
                            </div>
                            ` : ''}

                            <div class="mt-3 pt-3 border-top">
                                <div class="p-2 border rounded"> 
                                    <i class="fas fa-info-circle me-2 text-dark"></i>
                                    <strong>Note:</strong> ${data.note}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            resultContainer.innerHTML = cardHtml;
            resultContainer.style.display = 'block';
        }

        // Show basic query results
        function showBasicQueryResults(data) {
            // Remove existing card if any
            const existingCard = document.getElementById('verification-result-card');
            if (existingCard) {
                existingCard.innerHTML = '';
                existingCard.style.display = 'none';
            }

            const cardHtml = `
                    <div class="card border-0 shadow-sm">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-search me-2"></i>
                                    Basic Result
                                </h5>
                                <button type="button" class="btn btn-sm btn-outline-light" onclick="hideVerificationCard()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="border-bottom pb-2 mb-3 text-dark">
                                        <i class="fas fa-info-circle me-2"></i>Query Information
                                    </h6>
                                    <div class="info-row">
                                        <strong>Phone Number:</strong>
                                        <span>${data.phone_number}</span>
                                    </div>
                                    <div class="info-row">
                                        <strong>Supported:</strong>
                                        <span>${data.supported ? 'Yes' : 'No'}</span>
                                    </div>
                                    ${data.coverage_info && data.coverage_info.country ? `
                                    <div class="info-row">
                                        <strong>Country:</strong>
                                        <span>${data.coverage_info.country}</span>
                                    </div>
                                    ` : ''}
                                    ${data.coverage_info && data.coverage_info.country_code ? `
                                    <div class="info-row">
                                        <strong>Dialing Code:</strong>
                                        <span>+${data.coverage_info.country_code}</span>
                                    </div>
                                    ` : ''}
                                    ${data.coverage_info && (data.coverage_info.network_name || data.coverage_info.carrier_name) ? `
                                    <div class="info-row">
                                        <strong>Network/Carrier:</strong>
                                        <span>${data.coverage_info.network_name || data.coverage_info.carrier_name}</span>
                                    </div>
                                    ` : ''}
                                    ${data.coverage_info && (data.coverage_info.live_coverage !== undefined || data.coverage_info.support_provider !== undefined) ? `
                                    <div class="info-row">
                                        <strong>Status:</strong>
                                        <span>${(data.coverage_info.live_coverage || data.coverage_info.support_provider) ? 'Active' : 'Inactive'}</span>
                                    </div>
                                    ` : ''}
                                </div>
                            </div>
                            <div class="mt-3 pt-3 border-top">
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Note:</strong> ${data.note}
                                    ${!data.supported ? '' : ' To get detailed verification data, use the ADVANCED query option.'}
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
                    </style>
                `;

            existingCard.innerHTML = cardHtml;
            existingCard.style.display = 'block';

            // Scroll to the card
            existingCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
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

            // Provider badge
            const provider = verificationData.provider || 'Unknown';
            const providerClass = provider === 'TMT' ? 'primary' : provider === 'IPQS' ? 'info' : 'secondary';

            // Cost formatting
            const cost = verificationData.cost ? '$' + parseFloat(verificationData.cost).toFixed(6) : 'N/A';

            // Status handling - for TMT (status field) vs IPQS (valid field)
            let statusClass = 'secondary';
            let statusText = 'Unknown';

            if (provider === 'TMT' && verificationData.tmt_status !== undefined) {
                statusClass = verificationData.tmt_status === 0 ? 'success' : 'danger';
                statusText = verificationData.tmt_status === 0 ? 'Success' : 'Failed';
            } else if (provider === 'IPQS' && verificationData.ipqs_valid !== undefined) {
                statusClass = verificationData.ipqs_valid ? 'success' : 'danger';
                statusText = verificationData.ipqs_valid ? 'Valid' : 'Invalid';
            }

            // Valid field - mainly for IPQS
            let validClass = 'secondary';
            let validText = 'N/A';
            if (verificationData.ipqs_valid !== undefined) {
                validClass = verificationData.ipqs_valid ? 'success' : 'danger';
                validText = verificationData.ipqs_valid ? 'Yes' : 'No';
            } else if (verificationData.tmt_status !== undefined) {
                validClass = verificationData.tmt_status === 0 ? 'success' : 'danger';
                validText = verificationData.tmt_status === 0 ? 'Yes' : 'No';
            }

            // Present field
            let present = 'na';
            if (verificationData.tmt_present) {
                present = verificationData.tmt_present.toLowerCase();
            } else if (verificationData.ipqs_active !== undefined) {
                present = verificationData.ipqs_active ? 'yes' : 'no';
            }

            if (!['yes', 'no', 'na'].includes(present)) {
                present = 'na';
            }

            const presentText = present.charAt(0).toUpperCase() + present.slice(1);
            let presentClass = 'secondary';
            if (present === 'yes') {
                presentClass = 'success';
            } else if (present === 'no') {
                presentClass = 'warning';
            }

            // Ported field
            let portedClass = 'secondary';
            let portedText = 'N/A';
            if (verificationData.tmt_ported !== undefined) {
                portedClass = verificationData.tmt_ported ? 'warning' : 'success';
                portedText = verificationData.tmt_ported ? 'Yes' : 'No';
            }

            // Country - combine TMT/IPQS
            const country = verificationData.ipqs_country || verificationData.tmt_country || 'Unknown';

            // Network/Carrier - combine TMT/IPQS
            const network = verificationData.tmt_network || verificationData.ipqs_carrier || 'Unknown';

            // Fraud Score - IPQS only
            const fraudScore = verificationData.ipqs_fraud_score || 'N/A';

            const rowData = [
                phoneNumber,
                `<span class="badge badge-pill badge-outline-${providerClass} p-2 m-1">${provider}</span>`,
                cost,
                country,
                network,
                `<span class="badge badge-pill badge-outline-${statusClass} p-2 m-1">${statusText}</span>`,
                `<span class="badge badge-pill badge-outline-${validClass} p-2 m-1">${validText}</span>`,
                `<span class="badge badge-pill badge-outline-${presentClass} p-2 m-1">${presentText}</span>`,
                `<span class="badge badge-pill badge-outline-${portedClass} p-2 m-1">${portedText}</span>`,
                fraudScore,
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
                $(newNode).on('click', function () {
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

            table.rows().every(function (rowIdx, tableLoop, rowLoop) {
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
                verifications.forEach(function (verification) {
                    // Provider badge
                    const provider = verification.provider || 'Unknown';
                    const providerClass = provider === 'TMT' ? 'primary' : provider === 'IPQS' ? 'info' : 'secondary';

                    // Cost formatting
                    const cost = verification.cost ? '$' + parseFloat(verification.cost).toFixed(6) : 'N/A';

                    // Status handling - for TMT (status field) vs IPQS (valid field)
                    let statusClass = 'secondary';
                    let statusText = 'Unknown';

                    if (provider === 'TMT' && verification.tmt_status !== undefined) {
                        statusClass = verification.tmt_status === 0 ? 'success' : 'danger';
                        statusText = verification.tmt_status === 0 ? 'Success' : 'Failed';
                    } else if (provider === 'IPQS' && verification.ipqs_valid !== undefined) {
                        statusClass = verification.ipqs_valid ? 'success' : 'danger';
                        statusText = verification.ipqs_valid ? 'Valid' : 'Invalid';
                    }

                    // Valid field - mainly for IPQS
                    let validClass = 'secondary';
                    let validText = 'N/A';
                    if (verification.ipqs_valid !== undefined) {
                        validClass = verification.ipqs_valid ? 'success' : 'danger';
                        validText = verification.ipqs_valid ? 'Yes' : 'No';
                    } else if (verification.tmt_status !== undefined) {
                        validClass = verification.tmt_status === 0 ? 'success' : 'danger';
                        validText = verification.tmt_status === 0 ? 'Yes' : 'No';
                    }

                    // Present field
                    let present = 'na';
                    if (verification.tmt_present) {
                        present = verification.tmt_present.toLowerCase();
                    } else if (verification.ipqs_active !== undefined) {
                        present = verification.ipqs_active ? 'yes' : 'no';
                    }

                    if (!['yes', 'no', 'na'].includes(present)) {
                        present = 'na';
                    }

                    const presentText = present.charAt(0).toUpperCase() + present.slice(1);
                    let presentClass = 'secondary';
                    if (present === 'yes') {
                        presentClass = 'success';
                    } else if (present === 'no') {
                        presentClass = 'warning';
                    }

                    // Ported field
                    let portedClass = 'secondary';
                    let portedText = 'N/A';
                    if (verification.tmt_ported !== undefined) {
                        portedClass = verification.tmt_ported ? 'warning' : 'success';
                        portedText = verification.tmt_ported ? 'Yes' : 'No';
                    }

                    // Country - combine TMT/IPQS
                    const country = verification.ipqs_country || verification.tmt_country || 'Unknown';

                    // Network/Carrier - combine TMT/IPQS
                    const network = verification.tmt_network || verification.ipqs_carrier || 'Unknown';

                    // Fraud Score - IPQS only
                    const fraudScore = verification.ipqs_fraud_score || 'N/A';

                    const rowData = [
                        verification.phone_number,
                        `<span class="badge badge-pill badge-outline-${providerClass} p-2 m-1">${provider}</span>`,
                        cost,
                        country,
                        network,
                        `<span class="badge badge-pill badge-outline-${statusClass} p-2 m-1">${statusText}</span>`,
                        `<span class="badge badge-pill badge-outline-${validClass} p-2 m-1">${validText}</span>`,
                        `<span class="badge badge-pill badge-outline-${presentClass} p-2 m-1">${presentText}</span>`,
                        `<span class="badge badge-pill badge-outline-${portedClass} p-2 m-1">${portedText}</span>`,
                        fraudScore,
                        new Date(verification.created_at).toLocaleDateString()
                    ];

                    // Add row with data-phone attribute
                    const newRow = table.row.add(rowData).draw(false);
                    const newNode = newRow.node();
                    $(newNode).attr('data-phone', verification.phone_number);
                    $(newNode).css('cursor', 'pointer');
                    $(newNode).attr('title', 'Click to view detailed verification results');

                    // Add click handler to show card
                    $(newNode).on('click', function () {
                        showVerificationCard(verification);
                    });
                });

                console.log(`Loaded ${verifications.length} verifications from API`);
            } else {
                console.log('No verifications returned from API');
            }

            table.draw();
        }

        document.addEventListener('DOMContentLoaded', function () {
            // Load verifications from server-side data
            loadVerifications();

            // Main form elements
            const verifyBtnMain = document.getElementById('verifyBtnMain');
            const phoneInputMain = document.getElementById('phone_number_main');
            const phoneErrorMain = document.getElementById('phone-error-main');
            const spinnerMain = verifyBtnMain.querySelector('.spinner-border');
            const basicQueryRadioMain = document.getElementById('basic_query_main');
            const advancedQueryRadioMain = document.getElementById('advanced_query_main');
            const advancedOptionsMain = document.getElementById('advanced-options-main');
            const verificationResultMain = document.getElementById('verification-result-main');


            let phoneValidationCache = {};

            // Handle query type selection for main form
            function updateQueryTypeDisplayMain() {
                if (advancedQueryRadioMain.checked) {
                    advancedOptionsMain.style.display = 'block';
                } else {
                    advancedOptionsMain.style.display = 'none';
                }

                // Re-validate phone number when query type changes to update cost display
                const phoneNumber = phoneInputMain.value.trim();
                if (phoneNumber && phoneNumber.length >= 5) {
                    // Force re-validation to update cost display for advanced queries
                    clearTimeout(phoneInputMain.validationTimeout);
                    phoneInputMain.validationTimeout = setTimeout(() => {
                        validateNetworkPrefixMain(phoneNumber);
                    }, 100);
                }
            }

            // Event listeners for main form
            basicQueryRadioMain.addEventListener('change', updateQueryTypeDisplayMain);
            advancedQueryRadioMain.addEventListener('change', updateQueryTypeDisplayMain);

            // Initialize display
            updateQueryTypeDisplayMain();

            // Real-time phone validation for main form

            async function validateNetworkPrefixMain(phoneNumber) {
                const validationInfo = document.getElementById('phone-validation-info-main');
                const validationAlert = document.getElementById('validation-alert-main');
                const detectedInfo = document.getElementById('detected-info-main');
                const coverageStatus = document.getElementById('coverage-status-main');
                const costInfo = document.getElementById('cost-info-main');
                const costAmount = document.getElementById('cost-amount-main');

                if (!phoneNumber || phoneNumber.length < 1) {
                    validationInfo.style.display = 'none';
                    return null;
                }

                const cleanNumber = phoneNumber.replace(/[^0-9]/g, '');

                if (cleanNumber.length === 0) {
                    validationInfo.style.display = 'none';
                    return null;
                }

                // Check cache first
                if (phoneValidationCache[cleanNumber]) {
                    const cachedResult = phoneValidationCache[cleanNumber];
                    displayNetworkValidationResultMain(cachedResult);
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
                    displayNetworkValidationResultMain(result);
                    return result;
                } catch (error) {
                    console.error('Network prefix validation error:', error);
                    validationInfo.style.display = 'none';
                    return null;
                }
            }

            function displayNetworkValidationResultMain(result) {
                const validationInfo = document.getElementById('phone-validation-info-main');
                const validationAlert = document.getElementById('validation-alert-main');
                const detectedInfo = document.getElementById('detected-info-main');
                const coverageStatus = document.getElementById('coverage-status-main');
                const costInfo = document.getElementById('cost-info-main');
                const costAmount = document.getElementById('cost-amount-main');
                const advancedQueryRadio = document.getElementById('advanced_query_main');
                const phoneInput = phoneInputMain.value.trim();
                const phoneLength = phoneInput.replace(/[^0-9]/g, '').length;

                if (result.success) {
                    const countryName = result.country_name || 'Unknown';
                    const networkName = result.network_name || 'Unknown';
                    const matchedPrefixLength = result.prefix_length || 0;

                    // Progressive detection logic:
                    // If user typed less than the matched prefix length, show ONLY country
                    // If user typed equals or more than matched prefix, show country + network
                    let displayText = '';

                    if (phoneLength < matchedPrefixLength) {
                        // User hasn't typed enough for operator - show only country
                        displayText = countryName;
                    } else {
                        // User typed the full prefix - show country + operator
                        displayText = `${countryName} - ${networkName} (${result.prefix})`;
                    }

                    // Enhanced display for international scenarios
                    if (result.ambiguous) {
                        // Multiple countries possible - show helpful information
                        detectedInfo.textContent = displayText;
                        coverageStatus.textContent = `Continue typing (${result.countries?.join(', ') || 'Multiple options'})`;
                        validationAlert.className = 'alert alert-warning mb-0';

                        phoneInputMain.classList.remove('is-invalid', 'is-valid');
                        phoneErrorMain.textContent = '';
                        phoneErrorMain.style.display = 'none';
                        verifyBtnMain.disabled = true;
                        verifyBtnMain.title = 'Continue typing to identify the country';
                    } else {
                        detectedInfo.textContent = displayText;

                        // Handle partial matches (country code inputs) - show info without errors
                        if (result.partial_match) {
                            validationAlert.className = 'alert alert-info mb-0';
                            coverageStatus.textContent = result.live_coverage ? 'Available' : 'Not Available';

                            // Don't show input as invalid during typing - just neutral
                            phoneInputMain.classList.remove('is-invalid', 'is-valid');
                            phoneErrorMain.textContent = ''; // No error message during typing
                            phoneErrorMain.style.display = 'none';

                            // Disable verify button for incomplete numbers (but don't show error)
                            verifyBtnMain.disabled = true;
                            verifyBtnMain.title = 'Complete the phone number to enable verification';
                        } else if (result.live_coverage) {
                            validationAlert.className = 'p-2 border rounded';
                            coverageStatus.textContent = 'Available - API call will be made';
                            phoneInputMain.classList.remove('is-invalid');
                            phoneInputMain.classList.add('is-valid');
                            phoneErrorMain.textContent = '';
                            phoneErrorMain.style.display = 'none';

                            // Enable verify button for complete valid numbers
                            verifyBtnMain.disabled = false;
                            verifyBtnMain.title = '';
                        } else {
                            validationAlert.className = 'alert alert-warning mb-0';
                            coverageStatus.textContent = 'Not Available - Verification disabled';
                            phoneInputMain.classList.remove('is-invalid');
                            phoneInputMain.classList.add('is-valid');
                            phoneErrorMain.textContent = '';
                            phoneErrorMain.style.display = 'none';

                            // For basic queries, still allow verification even without live coverage
                            if (basicQueryRadioMain.checked) {
                                verifyBtnMain.disabled = false;
                                verifyBtnMain.title = '';
                            } else {
                                verifyBtnMain.disabled = true;
                                verifyBtnMain.title = 'This operator does not support live coverage verification';
                            }
                        }

                        // Show cost information for advanced queries
                        if (result.cost && advancedQueryRadio.checked) {
                            costAmount.textContent = `$${result.cost}`;
                            costInfo.style.display = 'block';
                        } else {
                            costInfo.style.display = 'none';
                        }
                    }

                    validationInfo.style.display = 'block';
                } else {
                    validationAlert.className = 'alert alert-secondary mb-0';
                    detectedInfo.textContent = 'Network prefix not found in database';
                    coverageStatus.textContent = 'Unknown';
                    validationInfo.style.display = 'block';
                    phoneInputMain.classList.remove('is-valid');
                    phoneInputMain.classList.add('is-invalid');
                    phoneErrorMain.textContent = result.error || 'Network prefix not found';
                    phoneErrorMain.style.display = 'block';
                    costInfo.style.display = 'none';

                    // Disable verify button for invalid numbers
                    verifyBtnMain.disabled = true;
                    verifyBtnMain.title = 'Enter a valid phone number to enable verification';
                }
            }

            // MAIN FORM HANDLERS
            // Add real-time validation to main phone input
            phoneInputMain.addEventListener('input', function (e) {
                phoneErrorMain.textContent = '';
                phoneErrorMain.style.display = 'none';
                const phoneNumber = e.target.value.trim();

                // Start progressive detection at 1 digit to catch all country codes
                // Country codes can be 1-4 digits (e.g., 1=USA/Canada, 39=Italy, 971=UAE)
                // Prefixes can be 8-10 digits
                if (phoneNumber.length < 1) {
                    document.getElementById('phone-validation-info-main').style.display = 'none';
                    verifyBtnMain.disabled = true;
                    verifyBtnMain.title = 'Enter phone number';
                    phoneInputMain.classList.remove('is-valid', 'is-invalid');
                    return;
                }

                // For basic queries, use simple validation but still show network info
                if (basicQueryRadioMain.checked) {
                    if (phoneNumber.length >= 8) {
                        verifyBtnMain.disabled = false;
                        verifyBtnMain.title = '';
                        phoneInputMain.classList.remove('is-invalid');
                        phoneInputMain.classList.add('is-valid');
                    } else {
                        verifyBtnMain.disabled = true;
                        verifyBtnMain.title = 'Enter at least 8 digits';
                        phoneInputMain.classList.remove('is-valid', 'is-invalid');
                    }

                    // Show network info even for basic queries with faster response
                    clearTimeout(phoneInputMain.validationTimeout);
                    phoneInputMain.validationTimeout = setTimeout(() => {
                        validateNetworkPrefixMain(phoneNumber);
                    }, 300);
                    return;
                }

                // For advanced queries, use full validation
                verifyBtnMain.disabled = true;
                verifyBtnMain.title = 'Validating phone number...';

                clearTimeout(phoneInputMain.validationTimeout);
                phoneInputMain.validationTimeout = setTimeout(() => {
                    validateNetworkPrefixMain(phoneNumber);
                }, 300);
            });

            // Main verify button handler
            verifyBtnMain.addEventListener('click', async function () {
                const phoneNumber = phoneInputMain.value.trim();
                const queryType = document.querySelector('input[name="query_type_main"]:checked').value;
                const dataFreshness = document.getElementById('data_freshness_main').value;

                if (!phoneNumber) {
                    phoneInputMain.classList.add('is-invalid');
                    phoneErrorMain.textContent = 'Please enter a phone number';
                    phoneErrorMain.style.display = 'block';
                    return;
                }

                phoneInputMain.classList.remove('is-invalid');
                phoneErrorMain.textContent = '';
                phoneErrorMain.style.display = 'none';

                verifyBtnMain.disabled = true;
                spinnerMain.classList.remove('d-none');
                verifyBtnMain.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Verifying...';

                try {
                    if (queryType === 'basic') {
                        // Basic query
                        const response = await fetch('/verification/basic', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                phone_number: phoneNumber
                            })
                        });

                        const data = await response.json();
                        if (data.success) {
                            showBasicQueryResultsMain(data);
                        } else {
                            phoneInputMain.classList.add('is-invalid');
                            phoneErrorMain.textContent = data.message || 'Basic query failed';
                            phoneErrorMain.style.display = 'block';
                        }
                    } else {
                        // Advanced query
                        const requestBody = {
                            phone_number: phoneNumber
                        };

                        // Handle data freshness options
                        if (dataFreshness === 'all') {
                            requestBody.force_reverify = true;
                        } else if (dataFreshness === '30' || dataFreshness === '60' || dataFreshness === '90') {
                            // Pass data freshness for age-based refresh
                            requestBody.data_freshness = dataFreshness;
                        }

                        const response = await fetch('/verification/advanced', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify(requestBody)
                        });

                        const data = await response.json();
                        if (data.success) {
                            showVerificationCardMain(data.verification || data.data);
                            // Don't show table for single verification
                            // updateTableRow(data.verification);
                        } else {
                            phoneInputMain.classList.add('is-invalid');
                            phoneErrorMain.textContent = data.message || 'Advanced verification failed';
                            phoneErrorMain.style.display = 'block';
                        }
                    }
                } catch (error) {
                    console.error('Error:', error);
                    phoneInputMain.classList.add('is-invalid');
                    phoneErrorMain.textContent = 'Network error. Please try again.';
                    phoneErrorMain.style.display = 'block';
                } finally {
                    verifyBtnMain.disabled = false;
                    spinnerMain.classList.add('d-none');
                    verifyBtnMain.innerHTML = 'Verify';
                }
            });

            phoneInputMain.addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    verifyBtnMain.click();
                }
            });

            // Initial state for main form
            verifyBtnMain.disabled = true;
            verifyBtnMain.title = 'Enter a phone number to enable verification';

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
            fileUpload.addEventListener('change', function (e) {
                const file = e.target.files[0];
                if (!file) return;

                const reader = new FileReader();
                reader.onload = function (event) {
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

            async function showPreview(phoneNumbers) {
                if (phoneNumbers.length === 0) {
                    fileUpload.classList.add('is-invalid');
                    batchError.textContent = 'No valid phone numbers found in file';
                    filePreview.style.display = 'none';
                    return;
                }

                fileUpload.classList.remove('is-invalid');
                batchError.textContent = '';
                previewInfo.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Analyzing ${phoneNumbers.length} phone numbers...`;
                previewBody.innerHTML = '';
                filePreview.style.display = 'block';

                // Call cost preview API
                try {
                    const response = await fetch('/verification/batch/preview', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            phone_numbers: phoneNumbers
                        })
                    });

                    const costData = await response.json();

                    if (costData.success) {
                        showCostBreakdown(costData);
                    } else {
                        previewInfo.textContent = `Found ${phoneNumbers.length} phone numbers`;
                        showSimplePreview(phoneNumbers);
                    }
                } catch (error) {
                    console.error('Cost preview error:', error);
                    previewInfo.textContent = `Found ${phoneNumbers.length} phone numbers`;
                    showSimplePreview(phoneNumbers);
                }
            }

            function showSimplePreview(phoneNumbers) {
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
            }

            function showCostBreakdown(costData) {
                const totalCost = costData.total_cost;
                const avgCost = costData.avg_cost_per_number;
                const withCoverage = costData.numbers_with_coverage;
                const withoutCoverage = costData.numbers_without_coverage;
                const countries = costData.country_breakdown || [];

                // Simple summary message
                previewInfo.innerHTML = `
                        Found <strong>${costData.total_numbers}</strong> phone numbers.
                        Estimated cost: <strong>$${totalCost.toFixed(6)}</strong>
                        (${withCoverage} with coverage${withoutCoverage > 0 ? `, ${withoutCoverage} will be skipped` : ''})
                    `;

                // Minimal table view
                previewBody.innerHTML = `
                        ${countries.map(country => `
                            <tr>
                                <td>
                                    <strong>${country.country}</strong> (${country.count} numbers)
                                </td>
                                <td class="text-end">
                                    <strong>$${country.total_cost.toFixed(6)}</strong>
                                </td>
                            </tr>
                        `).join('')}
                        ${withoutCoverage > 0 ? `
                            <tr>
                                <td colspan="2">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        ${withoutCoverage} numbers without coverage will be skipped
                                    </small>
                                </td>
                            </tr>
                        ` : ''}
                    `;
            }

            batchVerifyBtn.addEventListener('click', function () {
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

                fetch('/verification/batch', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        phone_numbers: extractedPhoneNumbers,
                        data_freshness: document.getElementById('batch_data_freshness').value
                    })
                })
                    .then(response => {
                        console.log('Response status:', response.status);
                        console.log('Response headers:', response.headers);
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.text();
                    })
                    .then(text => {
                        console.log('Raw response:', text.substring(0, 200));
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('Failed to parse JSON:', e);
                            console.error('Response text:', text);
                            throw new Error('Invalid JSON response from server');
                        }
                    })
                    .then(data => {
                        if (data.success) {
                            $(batchModal).modal('hide');

                            // Show the verification table
                            document.getElementById('verification-table-container').style.display = 'block';

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
                        const spinner = batchVerifyBtn.querySelector('.spinner-border');
                        if (spinner) {
                            spinner.classList.add('d-none');
                        }
                        batchVerifyBtn.innerHTML = '<span class="spinner-border spinner-border-sm d-none" role="status"></span> Verify All';
                    });
            });

            $(batchModal).on('hidden.bs.modal', function () {
                fileUpload.value = '';
                document.getElementById('batch_data_freshness').value = '';
                fileUpload.classList.remove('is-invalid');
                batchError.textContent = '';
                filePreview.style.display = 'none';
                extractedPhoneNumbers = [];
                batchVerifyBtn.disabled = false;
                const spinner = batchVerifyBtn.querySelector('.spinner-border');
                if (spinner) {
                    spinner.classList.add('d-none');
                }
                batchVerifyBtn.innerHTML = '<span class="spinner-border spinner-border-sm d-none" role="status"></span> Verify All';
            });
        });
    </script>
@endsection

<!-- Filter Modal - YouTube Style -->
<div class="modal fade" id="filterModal" tabindex="-1" role="dialog" aria-labelledby="filterModalLabel"
    aria-hidden="true">
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
                        <h6 class="filter-category-title"
                            style="font-size: 14px; font-weight: 600; color: #666; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                            Coverage
                        </h6>
                        <div class="filter-options">
                            <select id="modal-coverage-filter" class="form-select"
                                style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 8px 12px; font-size: 14px;">
                                <option value="">All Coverage Types</option>
                                <option value="Yes">Live Coverage</option>
                                <option value="No">No Coverage</option>
                            </select>
                        </div>
                    </div>

                    <!-- Connection Category -->
                    <div class="filter-category mb-4">
                        <h6 class="filter-category-title"
                            style="font-size: 14px; font-weight: 600; color: #666; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                            Connection
                        </h6>
                        <div class="row">
                            <div class="col-6">
                                <select id="modal-type-filter" class="form-select"
                                    style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 8px 12px; font-size: 14px;">
                                    <option value="">All Types</option>
                                    <option value="Mobile">Mobile</option>
                                    <option value="Fixed">Fixed</option>
                                    <option value="Unknown">Unknown</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <select id="modal-ported-filter" class="form-select"
                                    style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 8px 12px; font-size: 14px;">
                                    <option value="">All Porting</option>
                                    <option value="Yes">Ported</option>
                                    <option value="No">Not Ported</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Status Category -->
                    <div class="filter-category mb-4">
                        <h6 class="filter-category-title"
                            style="font-size: 14px; font-weight: 600; color: #666; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                            Status
                        </h6>
                        <div class="row">
                            <div class="col-6">
                                <select id="modal-status-filter" class="form-select"
                                    style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 8px 12px; font-size: 14px;">
                                    <option value="">All Status</option>
                                    <option value="Success">Success</option>
                                    <option value="Failed">Failed</option>
                                    <option value="No Live Coverage">No Coverage</option>
                                    <option value="Error">Error</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <select id="modal-present-filter" class="form-select"
                                    style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 8px 12px; font-size: 14px;">
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