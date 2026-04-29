@extends('layouts.main')

@section('title', 'Customer Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Customers', 'url' => '#', 'icon' => 'bx bx-group']
             ]" />
        <h6 class="mb-0 text-uppercase">CUSTOMER LIST</h6>
        <hr />

        <!-- Dashboard Stats -->
        <div class="row row-cols-1 row-cols-lg-4">
            <div class="col mb-4">
                <div class="card radius-10">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1">Total Customers</p>
                            <h4 class="mb-0">{{ $customerCount ?? 0 }}</h4>
                        </div>
                        <div class="widgets-icons bg-gradient-burning text-white"><i class='bx bx-group'></i></div>
                    </div>
                </div>
            </div>
            <div class="col mb-4">
                <div class="card radius-10">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1">Total Borrowers</p>
                            <h4 class="mb-0">{{ $borrowerCount ?? 0 }}</h4>
                        </div>
                        <div class="widgets-icons bg-gradient-burning text-white"><i class='bx bx-user'></i></div>
                    </div>
                </div>
            </div>
            <div class="col mb-4">
                <div class="card radius-10">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1">Total Guarantors</p>
                            <h4 class="mb-0">{{ $guarantorCount ?? 0 }}</h4>
                        </div>
                        <div class="widgets-icons bg-gradient-burning text-white"><i class='bx bx-shield'></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customers Table -->
        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h6 class="card-title mb-0">Customers List</h6>
                            <div>
                                @can('create customer')
                                <a href="{{ route('customers.bulk-upload') }}" class="btn btn-success me-2">
                                    <i class="bx bx-upload"></i> Bulk Upload
                                </a>
                                <button type="button" class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#bulkPhoneUpdateModal">
                                    <i class="bx bx-phone"></i> Bulk Phone Update
                                </button>
                                <a href="{{ route('customers.create') }}" class="btn btn-primary">
                                    <i class="bx bx-plus"></i> Add Customer
                                </a>
                                @endcan
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped nowrap" id="customersTable">
                                <thead>
                                    <tr>
                                        <th>Customer No</th>
                                        <th>Name</th>
                                        <th>Phone</th>
                                        <th>Bank</th>
                                        <th>Account</th>
                                        <th>Region</th>
                                        <th>District</th>
                                        <th>Branch</th>
                                        <th>Category</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be loaded via Ajax -->
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Bulk Phone Update Modal -->
<div class="modal fade" id="bulkPhoneUpdateModal" tabindex="-1" aria-labelledby="bulkPhoneUpdateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkPhoneUpdateModalLabel">
                    <i class="bx bx-phone me-2"></i>Bulk Phone Update
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <strong>Upload columns:</strong> <code>name</code>, <code>bank_name</code>, <code>bank_account</code>, <code>phone1</code>.
                    System finds customer by <code>bank_name + bank_account</code> and updates <code>phone1</code> to <code>255XXXXXXXXX</code>.
                </div>

                <div class="mb-3">
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('customers.bulk-phone-update.template') }}">
                        <i class="bx bx-download me-1"></i>Download Template
                    </a>
                </div>

                <form id="bulkPhoneUpdateForm" action="{{ route('customers.bulk-phone-update.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="phone_file" class="form-label">Excel/CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="phone_file" id="phone_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                    </div>

                    <div id="phoneProgressContainer" class="mt-3" style="display:none;">
                        <div class="d-flex justify-content-between mb-2">
                            <span id="phoneProgressText">Processing...</span>
                            <span id="phoneProgressPercent">0%</span>
                        </div>
                        <div class="progress" style="height: 25px;">
                            <div id="phoneProgressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                                 role="progressbar" style="width:0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                <span id="phoneProgressBarText">0%</span>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary" id="phoneSubmitBtn">
                            <i class="bx bx-upload me-1"></i>Upload & Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Custom DataTables styling */
    .dataTables_processing {
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 20px;
        font-size: 16px;
        z-index: 9999;
    }
    
    .dataTables_length label,
    .dataTables_filter label {
        font-weight: 500;
        margin-bottom: 0;
    }
    
    .dataTables_filter input {
        border-radius: 6px;
        border: 1px solid #ddd;
        padding: 8px 12px;
        margin-left: 8px;
    }
    
    .table-responsive .table {
        margin-bottom: 0;
    }
    
    .avatar {
        flex-shrink: 0;
    }
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialize DataTable with Ajax
        var table = $('#customersTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("customers.data") }}',
                type: 'GET',
                error: function(xhr, error, code) {
                    console.error('DataTables Ajax Error:', error, code);
                    Swal.fire({
                        title: 'Error!',
                        text: 'Failed to load customers data. Please refresh the page.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            columns: [
                { data: 'customerNo', name: 'customerNo', title: 'Customer No' },
                { data: 'avatar_name', name: 'name', title: 'Name', orderable: true, searchable: true },
                { data: 'phone1', name: 'phone1', title: 'Phone' },
                { data: 'bank_name', name: 'bank_name', title: 'Bank' },
                { data: 'bank_account', name: 'bank_account', title: 'Account' },
                { data: 'region_name', name: 'region.name', title: 'Region' },
                { data: 'district_name', name: 'district.name', title: 'District' },
                { data: 'branch_name', name: 'branch.name', title: 'Branch' },
                { data: 'category', name: 'category', title: 'Category' },
                { data: 'actions', name: 'actions', title: 'Actions', orderable: false, searchable: false }
            ],
            responsive: true,
            order: [[1, 'asc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            language: {
                search: "",
                searchPlaceholder: "Search customers...",
                processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>',
                emptyTable: "No customers found",
                info: "Showing _START_ to _END_ of _TOTAL_ customers",
                infoEmpty: "Showing 0 to 0 of 0 customers",
                infoFiltered: "(filtered from _MAX_ total customers)",
                lengthMenu: "Show _MENU_ customers per page",
                zeroRecords: "No matching customers found"
            },
            columnDefs: [
                {
                    targets: -1, // Actions column
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                },
                {
                    targets: [0, 1, 2], // Priority columns for responsive
                    responsivePriority: 2
                }
            ],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            drawCallback: function(settings) {
                // Reinitialize tooltips after each draw
                $('[data-bs-toggle="tooltip"]').tooltip();
            }
        });

        // Handle delete button clicks
        $('#customersTable').on('click', '.delete-btn', function(e) {
            e.preventDefault();
            
            var customerId = $(this).data('id');
            var customerName = $(this).data('name');
            
            Swal.fire({
                title: 'Are you sure?',
                text: `You want to delete customer "${customerName}"? This action cannot be undone!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Create a form to submit the delete request
                    var form = $('<form>', {
                        'method': 'POST',
                        'action': '{{ route("customers.destroy", ":id") }}'.replace(':id', customerId)
                    });
                    
                    var csrfToken = $('<input>', {
                        'type': 'hidden',
                        'name': '_token',
                        'value': '{{ csrf_token() }}'
                    });
                    
                    var methodField = $('<input>', {
                        'type': 'hidden',
                        'name': '_method',
                        'value': 'DELETE'
                    });
                    
                    form.append(csrfToken, methodField);
                    $('body').append(form);
                    
                    // Show loading
                    Swal.fire({
                        title: 'Deleting...',
                        text: 'Please wait while we delete the customer.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    form.submit();
                }
            });
        });

        // Refresh table data function
        window.refreshCustomersTable = function() {
            table.ajax.reload(null, false);
        };
    });
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('bulkPhoneUpdateForm');
    if (!form) return;

    const submitBtn = document.getElementById('phoneSubmitBtn');
    const progressContainer = document.getElementById('phoneProgressContainer');

    function updateProgress(percent) {
        const bar = document.getElementById('phoneProgressBar');
        const barText = document.getElementById('phoneProgressBarText');
        const percentText = document.getElementById('phoneProgressPercent');
        bar.style.width = percent + '%';
        bar.setAttribute('aria-valuenow', percent);
        barText.textContent = Math.round(percent) + '%';
        percentText.textContent = Math.round(percent) + '%';
    }

    function pollProgress(importId) {
        const url = "{{ route('customers.bulk-phone-update.progress') }}" + "?import_id=" + encodeURIComponent(importId);
        const tick = () => {
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(p => {
                    if (!p || !p.status) return;
                    updateProgress(p.percentage || 0);
                    if (p.status === 'completed') {
                        updateProgress(100);
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="bx bx-upload me-1"></i>Upload & Update';
                        setTimeout(() => window.location.reload(), 800);
                        return;
                    }
                    setTimeout(tick, 1500);
                })
                .catch(() => setTimeout(tick, 2000));
        };
        tick();
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const fileInput = document.getElementById('phone_file');
        const file = fileInput.files[0];
        if (!file) return;

        progressContainer.style.display = 'block';
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i>Starting...';

        const formData = new FormData(form);
        fetch(form.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(r => r.json())
        .then(resp => {
            if (!resp || !resp.import_id) throw new Error(resp && resp.message ? resp.message : 'No import_id returned');
            pollProgress(resp.import_id);
        })
        .catch(err => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bx bx-upload me-1"></i>Upload & Update';
            alert(err.message || 'Upload failed');
        });
    });
});
</script>
@endpush