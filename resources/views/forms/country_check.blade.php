@extends('layouts.master')
@section('before-css')
@endsection

@section('main-content')
    <div class="breadcrumb">
        <h1>Carrier Check</h1>
        <ul>
            <li><a href="">Form</a></li>
            <li>Check Phone Carrier</li>
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
                <div class="card-title">Carrier Check</div>
                <button type="button" class="btn btn-info" data-toggle="modal" data-target="#carrierCheck">
                    Check Carrier
                </button>
            </div>
        </div>
    </div>

    <!-- Carrier Check Results -->
    <div class="row" id="carrierResults" style="display: none;">
        <div class="col-md-12 mb-4">
            <div class="card text-start">
                <div class="card-body">
                    <h4 class="card-title mb-3">Carrier Information</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Phone Number:</th>
                                    <td id="result-phone">-</td>
                                </tr>
                                <tr>
                                    <th>Country:</th>
                                    <td id="result-country">-</td>
                                </tr>
                                <tr>
                                    <th>Country Code:</th>
                                    <td id="result-country-code">-</td>
                                </tr>
                                <tr>
                                    <th>Carrier Prefix:</th>
                                    <td id="result-prefix">-</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Carrier Name:</th>
                                    <td id="result-carrier">-</td>
                                </tr>
                                <tr>
                                    <th>Network ID:</th>
                                    <td id="result-network-id">-</td>
                                </tr>
                                <tr>
                                    <th>MCC/MNC:</th>
                                    <td id="result-mcc-mnc">-</td>
                                </tr>
                                <tr>
                                    <th>Live Coverage:</th>
                                    <td id="result-live-coverage">
                                        <span class="badge badge-secondary">Unknown</span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="alert alert-info mt-3" id="api-recommendation" style="display: none;">
                        <strong>API Recommendation:</strong> <span id="api-recommendation-text"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Carrier Check Modal -->
    <div class="modal fade" id="carrierCheck" tabindex="-1" role="dialog" aria-labelledby="carrierCheckLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="carrierCheckLabel">Check Phone Carrier</h5>
                </div>
                <div class="modal-body">
                    <form id="carrierCheckForm">
                        @csrf
                        <div class="mb-3">
                            <label for="carrier_phone_number" class="col-form-label">Enter Phone Number</label>
                            <input type="tel" class="form-control" id="carrier_phone_number" name="phone_number"
                                placeholder="e.g., 85592313242" required>
                            <div class="form-text">Enter the full phone number including country code</div>
                            <div id="country-detection" class="mt-2" style="display: none;">
                                <div class="alert alert-info mb-0">
                                    <strong>Detected Country:</strong> <span id="detected-country"></span>
                                </div>
                            </div>
                            <div class="invalid-feedback" id="carrier-phone-error"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-info" id="checkCarrierBtn">
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                        Check Carrier
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-js')
@endsection

@section('bottom-js')
    <script src="{{ asset('assets/js/modal.script.js') }}"></script>

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

            // Auto-hide success messages after 8 seconds
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

        document.addEventListener('DOMContentLoaded', function() {
            // Country codes cache
            let countryCodesCache = null;

            // Fetch country codes from server
            async function fetchCountryCodes() {
                if (countryCodesCache) {
                    console.log('data found in cache');
                    return countryCodesCache;
                }

                try {
                    const response = await fetch('/country-check/country-codes');
                    if (response.ok) {
                        countryCodesCache = await response.json();
                        return countryCodesCache;
                    }
                } catch (error) {
                    console.error('Failed to fetch country codes:', error);
                }
                return {};
            }

            // Real-time country detection
            function detectCountry(phoneNumber) {
                const countryDetection = document.getElementById('country-detection');
                const detectedCountry = document.getElementById('detected-country');
                const carrierPhoneInput = document.getElementById('carrier_phone_number');

                if (!phoneNumber || phoneNumber.length < 1) {
                    countryDetection.style.display = 'none';
                    carrierPhoneInput.classList.remove('is-valid', 'is-invalid');
                    return;
                }

                // Clean phone number (remove non-digits)
                const cleanNumber = phoneNumber.replace(/[^0-9]/g, '');

                if (cleanNumber.length === 0) {
                    countryDetection.style.display = 'none';
                    carrierPhoneInput.classList.remove('is-valid', 'is-invalid');
                    return;
                }

                fetchCountryCodes().then(countryCodes => {
                    let foundCountry = null;

                    for (let i = Math.min(3, cleanNumber.length); i >= 1; i--) {
                        const possibleCode = cleanNumber.substring(0, i);
                        if (countryCodes[possibleCode]) {
                            foundCountry = {
                                code: possibleCode,
                                name: countryCodes[possibleCode].country_name,
                                iso2: countryCodes[possibleCode].iso2,
                                prefixes: countryCodes[possibleCode].prefixes || {}
                            };
                            break;
                        }
                    }

                    if (foundCountry) {
                        detectedCountry.textContent = `${foundCountry.name} (+${foundCountry.code})`;
                        countryDetection.style.display = 'block';
                        carrierPhoneInput.classList.remove('is-invalid');
                        carrierPhoneInput.classList.add('is-valid');
                    } else {
                        countryDetection.style.display = 'none';
                        if (cleanNumber.length >= 1) {
                            carrierPhoneInput.classList.remove('is-valid');
                            carrierPhoneInput.classList.add('is-invalid');
                            document.getElementById('carrier-phone-error').textContent =
                                'Invalid country code';
                        }
                    }
                    if (foundCountry) {
                        let companyName = null;
                        let companyCode = null;

                        // Check carrier prefixes if available
                        if (foundCountry.prefixes) {
                            for (let i = 3; i >= 2; i--) {
                                const possiblePrefix = cleanNumber.substring(foundCountry.code.length,
                                    foundCountry.code.length + i);
                                if (foundCountry.prefixes[possiblePrefix]) {
                                    companyCode = possiblePrefix;
                                    companyName = foundCountry.prefixes[possiblePrefix];
                                    break;
                                }
                            }
                        }

                        if (companyName) {
                            detectedCountry.textContent =
                                `${foundCountry.name} (+${foundCountry.code}) - ${companyName} (${companyCode})`;
                        } else {
                            detectedCountry.textContent =
                                `${foundCountry.name} (+${foundCountry.code})`;
                        }

                        countryDetection.style.display = 'block';
                        carrierPhoneInput.classList.remove('is-invalid');
                        carrierPhoneInput.classList.add('is-valid');
                    }

                });
            }

            // Carrier Check Functionality
            const checkCarrierBtn = document.getElementById('checkCarrierBtn');
            const carrierPhoneInput = document.getElementById('carrier_phone_number');
            const carrierPhoneError = document.getElementById('carrier-phone-error');
            const carrierModal = document.getElementById('carrierCheck');
            const carrierResults = document.getElementById('carrierResults');

            // Add real-time country detection
            carrierPhoneInput.addEventListener('input', function(e) {
                // Clear previous error messages when user starts typing
                carrierPhoneError.textContent = '';
                detectCountry(e.target.value);
            });

            checkCarrierBtn.addEventListener('click', function() {
                const phoneNumber = carrierPhoneInput.value.trim();

                if (!phoneNumber) {
                    carrierPhoneInput.classList.add('is-invalid');
                    carrierPhoneError.textContent = 'Please enter a phone number';
                    return;
                }

                // Remove previous error state
                carrierPhoneInput.classList.remove('is-invalid');
                carrierPhoneError.textContent = '';

                // Show loading state
                checkCarrierBtn.disabled = true;
                const carrierSpinner = checkCarrierBtn.querySelector('.spinner-border');
                carrierSpinner.classList.remove('d-none');
                checkCarrierBtn.innerHTML =
                    '<span class="spinner-border spinner-border-sm" role="status"></span> Checking...';

                // Make API call
                fetch('/country-check/check', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content')
                        },
                        body: JSON.stringify({
                            phone_number: phoneNumber
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            $(carrierModal).modal('hide');
                            displayCarrierResults(data);
                            showAlert('success', 'Success',
                                'Carrier information retrieved successfully!');
                        } else {
                            carrierPhoneInput.classList.add('is-invalid');
                            carrierPhoneError.textContent = data.error || 'Carrier check failed';
                            showAlert('warning', 'Warning', data.error || 'Carrier check failed.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        carrierPhoneInput.classList.add('is-invalid');
                        carrierPhoneError.textContent = 'Network error. Please try again.';
                        showAlert('danger', 'Error', 'Network error occurred. Please try again.');
                    })
                    .finally(() => {
                        checkCarrierBtn.disabled = false;
                        carrierSpinner.classList.add('d-none');
                        checkCarrierBtn.innerHTML = 'Check Carrier';
                    });
            });

            function displayCarrierResults(data) {
                document.getElementById('result-phone').textContent = data.phone_number || '-';
                document.getElementById('result-country').textContent = data.iso2 ? data.iso2.toUpperCase() : '-';
                document.getElementById('result-country-code').textContent = data.country_code || '-';
                document.getElementById('result-prefix').textContent = data.carrier_prefix || '-';
                document.getElementById('result-carrier').textContent = data.carrier_name || '-';
                document.getElementById('result-network-id').textContent = data.network_id || '-';

                const mccMnc = (data.mcc && data.mnc) ? `${data.mcc}/${data.mnc}` : '-';
                document.getElementById('result-mcc-mnc').textContent = mccMnc;


                const liveCoverageElement = document.getElementById('result-live-coverage');
                const apiRecommendation = document.getElementById('api-recommendation');
                const apiRecommendationText = document.getElementById('api-recommendation-text');

                if (data.live_coverage !== undefined) {
                    if (data.live_coverage) {
                        liveCoverageElement.innerHTML = '<span class="badge badge-success">Yes</span>';
                        apiRecommendationText.textContent =
                            'This number has live coverage. Send to API for verification.';
                        apiRecommendation.className = 'alert alert-success mt-3';
                    } else {
                        liveCoverageElement.innerHTML = '<span class="badge badge-danger">No</span>';
                        apiRecommendationText.textContent =
                            'This number has no live coverage. Skip API to save costs.';
                        apiRecommendation.className = 'alert alert-warning mt-3';
                    }
                    apiRecommendation.style.display = 'block';
                } else {
                    liveCoverageElement.innerHTML = '<span class="badge badge-secondary">Unknown</span>';
                    apiRecommendation.style.display = 'none';
                }

                carrierResults.style.display = 'block';
                carrierResults.scrollIntoView({
                    behavior: 'smooth'
                });
            }

            // Reset carrier modal when closed
            $(carrierModal).on('hidden.bs.modal', function() {
                carrierPhoneInput.value = '';
                carrierPhoneInput.classList.remove('is-invalid', 'is-valid');
                carrierPhoneError.textContent = '';
                document.getElementById('country-detection').style.display = 'none';
                checkCarrierBtn.disabled = false;
                const carrierSpinner = checkCarrierBtn.querySelector('.spinner-border');
                carrierSpinner.classList.add('d-none');
                checkCarrierBtn.innerHTML = 'Check Carrier';
            });

            carrierPhoneInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    checkCarrierBtn.click();
                }
            });
        });
    </script>
@endsection
