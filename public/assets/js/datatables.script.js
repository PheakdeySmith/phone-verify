$(document).ready(function () {
    // contact-list-table 
    $('#contact_list_table').DataTable({searching: false  });

    // zero table
    $('#zero_configuration_table').DataTable();

    // feature enable/disable

    $('#feature_disable_table').DataTable({
        "paging": false,
        "ordering": false,
        "info": false
    });

    // ordering or sorting

    if ($('#deafult_ordering_table').length) {
        var verifyTable = $('#deafult_ordering_table').DataTable({
            "order": [
                [8, "desc"] // Order by "Verified" column (last column) descending to show newest first
            ],
            "pageLength": 25,
            "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            "dom": '<"top"lf>rt<"bottom"ip><"clear">',
            "initComplete": function() {
                // Populate filter dropdowns with unique values from table data
                populateVerifyFilterDropdowns(this.api());

                // Setup filter event listeners
                setupVerifyTableFilters(this.api());
            }
        });
    }

    // network verification table with advanced filtering
    if ($('#network_verification_table').length) {
        var networkTable = $('#network_verification_table').DataTable({
            "order": [
                [10, "desc"] // Order by "Verified" column (last column) descending to show newest first
            ],
            "pageLength": 25,
            "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            "dom": '<"top"lf>rt<"bottom"ip><"clear">',
            "columnDefs": [
                {
                    "targets": [4, 6, 8, 9], // Live Coverage, Status, Ported, Present columns
                    "orderable": true,
                    "searchable": true
                }
            ],
            "initComplete": function() {
                // Setup filter event listeners
                setupTableFilters(this.api());
            }
        });
    }

    // multi column ordering
    $('#multicolumn_ordering_table').DataTable({
        columnDefs: [{
            targets: [0],
            orderData: [0, 1]
        }, {
            targets: [1],
            orderData: [1, 0]
        }, {
            targets: [4],
            orderData: [4, 0]
        }]
    });


    // hidden column
    $('#hidden_column_table').DataTable({
        responsive: true,
        "columnDefs": [{
            "targets": [2],
            "visible": false,
            "searchable": false
        },
        {
            "targets": [3],
            "visible": false
        }
        ]
    });


    // complex header 
    $('#complex_header_table').DataTable();

    // dom positioning
    $('#dom_positioning_table').DataTable({
        "dom": '<"top"i>rt<"bottom"flp><"clear">'
    });


    // alternative pagination
    $('#alternative_pagination_table').DataTable({
        "pagingType": "full_numbers"
    });

    // scroll vertical 
    $('#scroll_vertical_table').DataTable({
        "scrollY": "200px",
        "scrollCollapse": true,
        "paging": false
    });

    // scroll horizontal 
    $('#scroll_horizontal_table').DataTable({
        "scrollX": true
    });

    // scroll vertical dynamic height  
    $('#scroll_vertical_dynamic_height_table').DataTable({
        scrollY: '50vh',
        scrollCollapse: true,
        paging: false
    });

    // scroll vertical and horizontal 
    $('#scroll_horizontal_vertical_table').DataTable({
        "scrollY": 200,
        "scrollX": true
    });

    // comma decimal
    $('#comma_decimal_table').DataTable({
        "language": {
            "decimal": ",",
            "thousands": "."
        }
    });


    // language option
    $('#language_option_table').DataTable({
        "language": {
            "lengthMenu": "Display _MENU_ records per page",
            "zeroRecords": "Nothing found - sorry",
            "info": "Showing page _PAGE_ of _PAGES_",
            "infoEmpty": "No records available",
            "infoFiltered": "(filtered from _MAX_ total records)"
        }
    });

})

// Network verification table filter functions

function setupTableFilters(table) {
    // Store filter function ID to avoid duplicates
    var networkFilterFnIndex = -1;

    // Store active filters
    var activeFilters = {
        coverage: '',
        type: '',
        status: '',
        ported: '',
        present: ''
    };

    // Custom filtering function for network table
    function networkTableFilter(settings, data, dataIndex) {
        // Only apply to network verification table
        if (settings.nTable.id !== 'network_verification_table') {
            return true;
        }

        // Get filter values from activeFilters object
        var coverageFilter = activeFilters.coverage;
        var typeFilter = activeFilters.type;
        var statusFilter = activeFilters.status;
        var portedFilter = activeFilters.ported;
        var presentFilter = activeFilters.present;

        // Check text-based filters (columns without HTML)
        if (typeFilter && data[6].toLowerCase().indexOf(typeFilter.toLowerCase()) === -1) {
            return false;
        }

        // Check HTML badge filters by extracting text content
        if (coverageFilter) {
            var coverageText = $('<div>').html(data[5]).text().trim();
            if (coverageText !== coverageFilter) {
                return false;
            }
        }
        if (statusFilter) {
            var statusText = $('<div>').html(data[7]).text().trim();
            if (statusText.indexOf(statusFilter) === -1) {
                return false;
            }
        }
        if (portedFilter) {
            var portedText = $('<div>').html(data[8]).text().trim();
            if (portedText !== portedFilter) {
                return false;
            }
        }
        if (presentFilter) {
            var presentText = $('<div>').html(data[9]).text().trim();
            if (presentText !== presentFilter) {
                return false;
            }
        }

        return true;
    }

    // Add the filter function
    networkFilterFnIndex = $.fn.dataTable.ext.search.push(networkTableFilter) - 1;

    // Function to apply filters from modal
    function applyModalFilters() {
        activeFilters.coverage = $('#modal-coverage-filter').val();
        activeFilters.type = $('#modal-type-filter').val();
        activeFilters.status = $('#modal-status-filter').val();
        activeFilters.ported = $('#modal-ported-filter').val();
        activeFilters.present = $('#modal-present-filter').val();

        // Update filter status
        updateFilterStatus();

        // Apply filters to table
        table.draw();

        // Close modal
        $('#filterModal').modal('hide');
    }

    // Function to clear all filters
    function clearAllFilters() {
        activeFilters = {
            coverage: '',
            type: '',
            status: '',
            ported: '',
            present: ''
        };

        // Clear modal form
        $('#modal-filters select').val('');

        // Update filter status
        updateFilterStatus();

        // Apply filters to table
        table.draw();
    }

    // Function to update filter status indicator
    function updateFilterStatus() {
        var filterCount = 0;
        var activeFiltersList = [];

        Object.keys(activeFilters).forEach(function(key) {
            if (activeFilters[key]) {
                filterCount++;
                activeFiltersList.push(key.charAt(0).toUpperCase() + key.slice(1) + ': ' + activeFilters[key]);
            }
        });

        // Update filter button badge
        var filterCountBadge = $('#filterCount');
        if (filterCount > 0) {
            filterCountBadge.text(filterCount).removeClass('d-none');
            $('#filterBtn').removeClass('btn-info').addClass('btn-warning');
        } else {
            filterCountBadge.addClass('d-none');
            $('#filterBtn').removeClass('btn-warning').addClass('btn-info');
        }

        // Update active filters text in modal
        if (activeFiltersList.length > 0) {
            $('#activeFiltersText').text(activeFiltersList.join(', '));
        } else {
            $('#activeFiltersText').text('None');
        }
    }


    // Event handlers
    $('#applyFilters').on('click', applyModalFilters);
    $('#clearAllFilters').on('click', clearAllFilters);

    // Populate modal filters when modal opens
    $('#filterModal').on('show.bs.modal', function() {
        // Set current filter values in modal
        $('#modal-coverage-filter').val(activeFilters.coverage);
        $('#modal-type-filter').val(activeFilters.type);
        $('#modal-status-filter').val(activeFilters.status);
        $('#modal-ported-filter').val(activeFilters.ported);
        $('#modal-present-filter').val(activeFilters.present);

        updateFilterStatus();
    });

    // Update active filters text when modal filters change
    $('#modal-filters select').on('change', function() {
        var tempFilters = {
            coverage: $('#modal-coverage-filter').val(),
            type: $('#modal-type-filter').val(),
            status: $('#modal-status-filter').val(),
            ported: $('#modal-ported-filter').val(),
            present: $('#modal-present-filter').val()
        };

        var activeFiltersList = [];
        Object.keys(tempFilters).forEach(function(key) {
            if (tempFilters[key]) {
                activeFiltersList.push(key.charAt(0).toUpperCase() + key.slice(1) + ': ' + tempFilters[key]);
            }
        });

        if (activeFiltersList.length > 0) {
            $('#activeFiltersText').text(activeFiltersList.join(', '));
        } else {
            $('#activeFiltersText').text('None');
        }
    });

    // Initialize filter status
    updateFilterStatus();
}

// Regular verification table filter functions
function populateVerifyFilterDropdowns(table) {
    // Get unique values from each column
    var networks = [];
    var mccValues = [];

    table.rows().every(function() {
        var data = this.data();

        // Network (column 1)
        if (data[1] && data[1] !== 'Unknown' && networks.indexOf(data[1]) === -1) {
            networks.push(data[1]);
        }

        // MCC/MNC (column 2)
        if (data[2] && data[2] !== '/' && mccValues.indexOf(data[2]) === -1) {
            mccValues.push(data[2]);
        }
    });

    // Populate Network dropdown
    networks.sort();
    var networkSelect = $('#verify-network-filter');
    networks.forEach(function(network) {
        networkSelect.append('<option value="' + network + '">' + network + '</option>');
    });

    // Populate MCC/MNC dropdown
    mccValues.sort();
    var mccSelect = $('#verify-mcc-filter');
    mccValues.forEach(function(mcc) {
        mccSelect.append('<option value="' + mcc + '">' + mcc + '</option>');
    });
}

function setupVerifyTableFilters(table) {
    // Store filter function ID to avoid duplicates
    var verifyFilterFnIndex = -1;

    // Custom filtering function for verify table
    function verifyTableFilter(settings, data, dataIndex) {
        // Only apply to regular verification table
        if (settings.nTable.id !== 'deafult_ordering_table') {
            return true;
        }

        // Get filter values
        var networkFilter = $('#verify-network-filter').val();
        var mccFilter = $('#verify-mcc-filter').val();
        var typeFilter = $('#verify-type-filter').val();
        var statusFilter = $('#verify-status-filter').val();
        var portedFilter = $('#verify-ported-filter').val();
        var presentFilter = $('#verify-present-filter').val();

        // Check text-based filters (columns without HTML)
        if (networkFilter && data[1].indexOf(networkFilter) === -1) {
            return false;
        }
        if (mccFilter && data[2].indexOf(mccFilter) === -1) {
            return false;
        }
        if (typeFilter && data[3].toLowerCase().indexOf(typeFilter.toLowerCase()) === -1) {
            return false;
        }

        // Check HTML badge filters by extracting text content
        if (statusFilter) {
            var statusText = $('<div>').html(data[4]).text().trim();
            if (statusText.indexOf(statusFilter) === -1) {
                return false;
            }
        }
        if (portedFilter) {
            var portedText = $('<div>').html(data[5]).text().trim();
            if (portedText !== portedFilter) {
                return false;
            }
        }
        if (presentFilter) {
            var presentText = $('<div>').html(data[6]).text().trim();
            if (presentText !== presentFilter) {
                return false;
            }
        }

        return true;
    }

    // Add the filter function
    verifyFilterFnIndex = $.fn.dataTable.ext.search.push(verifyTableFilter) - 1;

    // Add event listeners for filter dropdowns
    $('#verify-table-filters select').on('change', function() {
        table.draw();
    });

    // Clear filters button
    $('#verify-clear-filters').on('click', function() {
        $('#verify-table-filters select').val('');
        table.draw();
    });
}